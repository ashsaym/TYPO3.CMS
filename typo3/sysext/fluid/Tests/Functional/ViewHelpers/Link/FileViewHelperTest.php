<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\CMS\Frontend\Resource\FileCollector;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\View\TemplateView;

final class FileViewHelperTest extends FunctionalTestCase
{
    private const TEMPLATE_PATH = 'EXT:fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Template.html';

    protected array $additionalFoldersToCreate = [
        '/fileadmin/user_upload',
        '/fileadmin/_processed_',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/user_upload/typo3_image2.jpg' => 'fileadmin/user_upload/typo3_image2.jpg',
        'typo3/sysext/fluid/Tests/Functional/Fixtures/ViewHelpers/Link/FileViewHelper/Folders/fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg' => 'fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ViewHelpers/Link/FileViewHelper/DatabaseImport.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    protected function tearDown(): void
    {
        $filesToDelete = [
            Environment::getPublicPath() . '/fileadmin/user_upload/typo3_image2.jpg',
            Environment::getPublicPath() . '/fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg',
        ];

        foreach ($filesToDelete as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function throwsExceptionOnMissingFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1621511632);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        (new TemplateView($context))->render();
    }

    #[Test]
    public function renderTagsForPublicFileTest(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getFile(1));

        self::assertEquals(
            [
                '<a title="uid-1" href="fileadmin/user_upload/typo3_image2.jpg">typo3_image2.jpg</a>',
                '<a target="_blank" title="uid-1" href="fileadmin/user_upload/typo3_image2.jpg">Link file</a>',
                '<a title="uid-1" href="fileadmin/user_upload/typo3_image2.jpg">Link file - alt-name.jpg</a>',
                '<a title="uid-1" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file</a>',
                '<a title="uid-1" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt name not valid</a>',
                '<a title="uid-1" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt name wrong extension</a>',
                '<a title="uid-1" download="alt-name.jpg" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt-name.jpg</a>',
                '<a title="uid-1" download="alt-name.jpg" href="fileadmin/user_upload/typo3_image2.jpg">Download file - extension is appended</a>',
                '<a title="uid-1" href="fileadmin/user_upload/typo3_image2.jpg">4711</a>',
            ],
            array_values(array_filter(explode(LF, $view->render())))
        );
    }

    #[Test]
    public function renderTagsForNonPublicFileTest(): void
    {
        // Set storage to non-public
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_storage');
        $connection->update('sys_file_storage', ['is_public' => 0], ['uid' => 1]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getFile(1));

        $expected = [
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=f&amp;f=1&amp;token=',
        ];

        foreach (array_values(array_filter(explode(LF, $view->render()))) as $key => $tag) {
            self::assertStringContainsString($expected[$key], $tag);
        }
    }

    #[Test]
    public function renderTagsForPublicFileReferenceTest(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getFileReference(2));

        self::assertEquals(
            [
                '<a title="uid-2" href="fileadmin/user_upload/typo3_image2.jpg">typo3_image2.jpg</a>',
                '<a target="_blank" title="uid-2" href="fileadmin/user_upload/typo3_image2.jpg">Link file</a>',
                '<a title="uid-2" href="fileadmin/user_upload/typo3_image2.jpg">Link file - alt-name.jpg</a>',
                '<a title="uid-2" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file</a>',
                '<a title="uid-2" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt name not valid</a>',
                '<a title="uid-2" download="" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt name wrong extension</a>',
                '<a title="uid-2" download="alt-name.jpg" href="fileadmin/user_upload/typo3_image2.jpg">Download file - alt-name.jpg</a>',
                '<a title="uid-2" download="alt-name.jpg" href="fileadmin/user_upload/typo3_image2.jpg">Download file - extension is appended</a>',
                '<a title="uid-2" href="fileadmin/user_upload/typo3_image2.jpg">4711</a>',
            ],
            array_values(array_filter(explode(LF, $view->render())))
        );
    }

    #[Test]
    public function renderTagsForNonPublicFileReferenceTest(): void
    {
        // Set storage to non-public
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_storage');
        $connection->update('sys_file_storage', ['is_public' => 0], ['uid' => 1]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getFileReference(2));

        $expected = [
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=r&amp;r=2&amp;token=',
        ];

        foreach (array_values(array_filter(explode(LF, $view->render()))) as $key => $tag) {
            self::assertStringContainsString($expected[$key], $tag);
        }
    }

    #[Test]
    public function renderTagsForPublicProcessedFileTest(): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getProcessedFile(3));

        self::assertEquals(
            [
                '<a title="uid-3" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">csm_typo3_image2_5c2670fd59.jpg</a>',
                '<a target="_blank" title="uid-3" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Link file</a>',
                '<a title="uid-3" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Link file - alt-name.jpg</a>',
                '<a title="uid-3" download="" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Download file</a>',
                '<a title="uid-3" download="" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Download file - alt name not valid</a>',
                '<a title="uid-3" download="" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Download file - alt name wrong extension</a>',
                '<a title="uid-3" download="alt-name.jpg" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Download file - alt-name.jpg</a>',
                '<a title="uid-3" download="alt-name.jpg" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">Download file - extension is appended</a>',
                '<a title="uid-3" href="fileadmin/_processed_/csm_typo3_image2_5c2670fd59.jpg">4711</a>',
            ],
            array_values(array_filter(explode(LF, $view->render())))
        );
    }

    #[Test]
    public function renderTagsForNonPublicProcessedFileTest(): void
    {
        // Set storage to non-public
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_storage');
        $connection->update('sys_file_storage', ['is_public' => 0], ['uid' => 1]);

        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplatePathAndFilename(self::TEMPLATE_PATH);
        $view = new TemplateView($context);
        $view->assign('file', $this->getProcessedFile(3));

        $expected = [
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;dl=1&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;dl=1&amp;fn=alt-name.jpg&amp;token=',
            'index.php?eID=dumpFile&amp;t=p&amp;p=3&amp;token=',
        ];

        foreach (array_values(array_filter(explode(LF, $view->render()))) as $key => $tag) {
            self::assertStringContainsString($expected[$key], $tag);
        }
    }

    protected function getFile(int $fileUid): File
    {
        return $this->get(ResourceFactory::class)->retrieveFileOrFolderObject($fileUid);
    }

    protected function getFileReference(int $fileUid): FileReference
    {
        $fileCollector = $this->get(FileCollector::class);
        $fileCollector->addFileReferences([$fileUid]);
        $fileReferences = $fileCollector->getFiles();

        return reset($fileReferences);
    }

    protected function getProcessedFile(int $fileUid): ProcessedFile
    {
        return $this->get(ProcessedFileRepository::class)->findByUid($fileUid);
    }
}
