<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Resource;

use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourcePermissionsUnavailableException;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Search\Result\FileSearchResultInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A folder that groups files in a storage. This may be a folder on the local
 * disk, a bucket in Amazon S3 or a user or a tag in Flickr.
 *
 * This object is not persisted in TYPO3 locally, but created on the fly by
 * storage drivers for the folders they "offer".
 *
 * Some folders serve as a physical container for files (e.g. folders on the
 * local disk, S3 buckets or Flickr users). Other folders just group files by a
 * certain criterion, e.g. a tag.
 * The way this is implemented depends on the storage driver.
 */
class Folder implements FolderInterface
{
    /**
     * The storage this folder belongs to.
     */
    protected ResourceStorage $storage;

    /**
     * The identifier of this folder to identify it on the storage.
     * On some drivers, this is the path to the folder, but drivers could also just
     * provide any other unique identifier for this folder on the specific storage.
     */
    protected string $identifier;

    /**
     * The name of this folder
     */
    protected string $name;

    /**
     * The filters this folder should use for a filelist.
     *
     * @var callable[]
     */
    protected array $fileAndFolderNameFilters = [];

    /**
     * Modes for filter usage in getFiles()/getFolders()
     */
    public const FILTER_MODE_NO_FILTERS = 0;
    // Merge local filters into storage's filters
    public const FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS = 1;
    // Only use the filters provided by the storage
    public const FILTER_MODE_USE_STORAGE_FILTERS = 2;
    // Only use the filters provided by the current class
    public const FILTER_MODE_USE_OWN_FILTERS = 3;

    public function __construct(ResourceStorage $storage, string $identifier, string $name)
    {
        $this->storage = $storage;
        $this->identifier = $identifier;
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the full path of this folder, from the root.
     *
     * @param string|null $rootId ID of the root folder, NULL to auto-detect
     */
    public function getReadablePath(?string $rootId = null): string
    {
        if ($rootId === null) {
            // Find first matching file mount and use that as root
            foreach ($this->storage->getFileMounts() as $fileMount) {
                if ($this->storage->isWithinFolder($fileMount['folder'], $this)) {
                    $rootId = $fileMount['folder']->getIdentifier();
                    break;
                }
            }
            if ($rootId === null) {
                $rootId = $this->storage->getRootLevelFolder()->getIdentifier();
            }
        }
        $readablePath = '/';
        if ($this->identifier !== $rootId) {
            try {
                $readablePath = $this->getParentFolder()->getReadablePath($rootId);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                // May have no access to parent folder (e.g. because of mount point)
                $readablePath = '/';
            }
        }
        return $readablePath . ($this->name ? $this->name . '/' : '');
    }

    /**
     * Sets a new name of the folder
     * currently this does not trigger the "renaming process"
     * as the name is more seen as a label
     *
     * @param string $name The new name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStorage(): ResourceStorage
    {
        return $this->storage;
    }

    /**
     * Returns the path of this folder inside the storage. It depends on the
     * type of storage whether this is a real path or just some unique identifier.
     *
     * @return non-empty-string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getHashedIdentifier(): string
    {
        return $this->storage->hashFileIdentifier($this->identifier);
    }

    /**
     * Returns a combined identifier of this folder, i.e. the storage UID and
     * the folder identifier separated by a colon ":".
     *
     * @return string Combined storage and folder identifier, e.g. StorageUID:folder/path/
     */
    public function getCombinedIdentifier(): string
    {
        return $this->getStorage()->getUid() . ':' . $this->getIdentifier();
    }

    /**
     * Returns a publicly accessible URL for this folder
     *
     * WARNING: Access to the folder may be restricted by further means, e.g. some
     * web-based authentication. You have to take care of this yourself.
     *
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl(): ?string
    {
        return $this->getStorage()->getPublicUrl($this);
    }

    /**
     * Returns a list of files in this folder, optionally filtered. There are several filter modes available, see the
     * FILTER_MODE_* constants for more information.
     *
     * For performance reasons the returned items can also be limited to a given range
     *
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @param string $sort Property name used to sort the items.
     *                     Among them may be: '' (empty, no sorting), name,
     *                     fileext, size, tstamp and rw.
     *                     If a driver does not support the given property, it
     *                     should fall back to "name".
     * @param bool $sortRev TRUE to indicate reverse sorting (last to first)
     * @return File[]
     */
    public function getFiles(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false, string $sort = '', bool $sortRev = false): array
    {
        if ($filterMode === 0) {
            $useFilters = false;
            $backedUpFilters = [];
        } else {
            [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        }

        $fileObjects = $this->storage->getFilesInFolder($this, $start, $numberOfItems, $useFilters, $recursive, $sort, $sortRev);

        $this->restoreBackedUpFiltersInStorage($backedUpFilters);

        return $fileObjects;
    }

    /**
     * Returns a file search result based on the given demand.
     * The result also includes matches in meta-data fields that are defined in TCA.
     *
     * @param int $filterMode The filter mode to use for the found files
     */
    public function searchFiles(FileSearchDemand $searchDemand, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS): FileSearchResultInterface
    {
        [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        $searchResult = $this->storage->searchFiles($searchDemand, $this, $useFilters);
        $this->restoreBackedUpFiltersInStorage($backedUpFilters);

        return $searchResult;
    }

    /**
     * Returns amount of all files within this folder, optionally filtered by
     * the given pattern
     *
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function getFileCount(array $filterMethods = [], bool $recursive = false): int
    {
        return $this->storage->countFilesInFolder($this, true, $recursive);
    }

    /**
     * Returns the object for a subfolder of the current folder if it exists,
     * or throws a FolderDoesNotExistException.
     *
     * @throws FolderDoesNotExistException
     */
    public function getSubfolder(string $name): Folder
    {
        if (!$this->storage->hasFolderInFolder($name, $this)) {
            throw new FolderDoesNotExistException('Folder "' . $name . '" does not exist in "' . $this->identifier . '"', 1329836110);
        }
        return $this->storage->getFolderInFolder($name, $this);
    }

    /**
     * @param int $start The item to start at
     * @param int $numberOfItems The number of items to return
     * @param int $filterMode The filter mode to use for the filelist.
     * @phpstan-return array<array-key, Folder>
     */
    public function getSubfolders(int $start = 0, int $numberOfItems = 0, int $filterMode = self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, bool $recursive = false): array
    {
        [$backedUpFilters, $useFilters] = $this->prepareFiltersInStorage($filterMode);
        $folderObjects = $this->storage->getFoldersInFolder($this, $start, $numberOfItems, $useFilters, $recursive);
        $this->restoreBackedUpFiltersInStorage($backedUpFilters);
        return $folderObjects;
    }

    /**
     * Adds a file from the local server disk. If the file already exists and
     * overwriting is disabled,
     *
     * @throws ExistingTargetFileNameException
     */
    public function addFile(string $localFilePath, ?string $fileName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::CANCEL): File
    {
        $fileName = $fileName ?: PathUtility::basename($localFilePath);

        return $this->storage->addFile($localFilePath, $this, $fileName, $conflictMode);
    }

    /**
     * Adds an uploaded file into the Storage.
     *
     * @param array|UploadedFileInterface $uploadedFileData Information about the uploaded file given by $_FILES['file1']
     *                                                      or a PSR-7 UploadedFileInterface object
     */
    public function addUploadedFile(array|UploadedFileInterface $uploadedFileData, DuplicationBehavior $conflictMode = DuplicationBehavior::CANCEL): FileInterface
    {
        return $this->storage->addUploadedFile($uploadedFileData, $this, null, $conflictMode);
    }

    /**
     * Renames this folder.
     */
    public function rename(string $newName): self
    {
        return $this->storage->renameFolder($this, $newName);
    }

    /**
     * Deletes this folder from its storage. This also means that this object becomes useless.
     */
    public function delete(bool $deleteRecursively = true): bool
    {
        return $this->storage->deleteFolder($this, $deleteRecursively);
    }

    /**
     * Creates a new blank file
     *
     * @param string $fileName
     * @return File The new file object
     */
    public function createFile(string $fileName): File
    {
        return $this->storage->createFile($fileName, $this);
    }

    /**
     * Creates a new folder
     *
     * @throws ExistingTargetFolderException
     * @throws InsufficientFolderWritePermissionsException
     */
    public function createFolder(string $folderName): Folder
    {
        return $this->storage->createFolder($folderName, $this);
    }

    /**
     * Copies folder to a target folder
     *
     * @param Folder $targetFolder Target folder to copy to.
     * @param string|null $targetFolderName an optional destination fileName
     * @param DuplicationBehavior $conflictMode
     * @return Folder New (copied) folder object.
     */
    public function copyTo(Folder $targetFolder, ?string $targetFolderName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::RENAME): Folder
    {
        return $targetFolder->getStorage()->copyFolder($this, $targetFolder, $targetFolderName, $conflictMode);
    }

    /**
     * Moves folder to a target folder
     *
     * @param Folder $targetFolder Target folder to move to.
     * @param string|null $targetFolderName an optional destination fileName
     * @param DuplicationBehavior $conflictMode
     * @return Folder New (copied) folder object.
     */
    public function moveTo(Folder $targetFolder, ?string $targetFolderName = null, DuplicationBehavior $conflictMode = DuplicationBehavior::RENAME): Folder
    {
        return $targetFolder->getStorage()->moveFolder($this, $targetFolder, $targetFolderName, $conflictMode);
    }

    /**
     * Checks if a file exists in this folder
     */
    public function hasFile(string $name): bool
    {
        return $this->storage->hasFileInFolder($name, $this);
    }

    /**
     * Fetches a file from a folder, must be a direct descendant of a folder.
     */
    public function getFile(string $fileName): ?FileInterface
    {
        if ($this->storage->hasFileInFolder($fileName, $this)) {
            return $this->storage->getFileInFolder($fileName, $this);
        }
        return null;
    }

    /**
     * Checks if a folder exists in this folder.
     */
    public function hasFolder(string $name): bool
    {
        return $this->storage->hasFolderInFolder($name, $this);
    }

    /**
     * Check if a file operation (= action) is allowed on this folder
     *
     * @param string $action Action that can be read, write or delete
     */
    public function checkActionPermission(string $action): bool
    {
        try {
            return $this->getStorage()->checkFolderActionPermission($action, $this);
        } catch (ResourcePermissionsUnavailableException $e) {
            return false;
        }
    }

    /**
     * Updates the properties of this folder, e.g. after re-indexing or moving it.
     *
     * NOTE: This method should not be called from outside the File Abstraction Layer (FAL)!
     *
     * @param array $properties
     * @internal
     */
    public function updateProperties(array $properties): void
    {
        // Setting identifier and name to update values
        if (isset($properties['identifier'])) {
            $this->identifier = $properties['identifier'];
        }
        if (isset($properties['name'])) {
            $this->name = $properties['name'];
        }
    }

    /**
     * Prepares the filters in this folder's storage according to a set filter mode.
     *
     * @param int $filterMode The filter mode to use; one of the FILTER_MODE_* constants
     * @return array The backed up filters as an array (NULL if filters were not backed up) and whether to use filters or not (bool)
     */
    protected function prepareFiltersInStorage(int $filterMode): array
    {
        $backedUpFilters = null;
        $useFilters = true;

        switch ($filterMode) {
            case self::FILTER_MODE_USE_OWN_FILTERS:
                $backedUpFilters = $this->storage->getFileAndFolderNameFilters();
                $this->storage->setFileAndFolderNameFilters($this->fileAndFolderNameFilters);

                break;

            case self::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS:
                if (!empty($this->fileAndFolderNameFilters)) {
                    $backedUpFilters = $this->storage->getFileAndFolderNameFilters();
                    foreach ($this->fileAndFolderNameFilters as $filter) {
                        $this->storage->addFileAndFolderNameFilter($filter);
                    }
                }

                break;

            case self::FILTER_MODE_USE_STORAGE_FILTERS:
                // nothing to do here

                break;

            case self::FILTER_MODE_NO_FILTERS:
                $useFilters = false;

                break;
        }
        return [$backedUpFilters, $useFilters];
    }

    /**
     * Restores the filters of a storage.
     *
     * @param array|null $backedUpFilters The filters to restore; might be NULL if no filters have been backed up, in
     *                               which case this method does nothing.
     * @see prepareFiltersInStorage()
     */
    protected function restoreBackedUpFiltersInStorage(?array $backedUpFilters): void
    {
        if ($backedUpFilters !== null) {
            $this->storage->setFileAndFolderNameFilters($backedUpFilters);
        }
    }

    /**
     * Sets the filters to use when listing files. These are only used if the filter mode is one of
     * FILTER_MODE_USE_OWN_FILTERS and FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS
     */
    public function setFileAndFolderNameFilters(array $filters): void
    {
        $this->fileAndFolderNameFilters = $filters;
    }

    /**
     * Returns the role of this folder (if any). See FolderInterface::ROLE_* constants for possible values.
     */
    public function getRole(): string
    {
        return $this->storage->getRole($this);
    }

    /**
     * Returns the parent folder.
     *
     * In non-hierarchical storages, that always is the root folder.
     *
     * The parent folder of the root folder is the root folder.
     *
     * @throws InsufficientFolderAccessPermissionsException
     */
    public function getParentFolder(): Folder
    {
        return $this->getStorage()->getFolder($this->getStorage()->getFolderIdentifierFromFileIdentifier($this->getIdentifier()));
    }

    /**
     * Returns the modification time of the file as Unix timestamp
     */
    public function getModificationTime(): int
    {
        return (int)$this->storage->getFolderInfo($this)['mtime'];
    }

    /**
     * Returns the creation time of the file as Unix timestamp
     */
    public function getCreationTime(): int
    {
        return (int)$this->storage->getFolderInfo($this)['ctime'];
    }
}
