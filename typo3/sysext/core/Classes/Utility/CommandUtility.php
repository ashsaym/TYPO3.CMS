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

namespace TYPO3\CMS\Core\Utility;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;

/**
 * Class to handle system commands.
 * finds executables (programs) on Unix and Windows without knowing where they are
 *
 * returns exec command for a program
 * or FALSE
 *
 * This class is meant to be used without instance:
 *
 * ```
 * $cmd = CommandUtility::getCommand ('awstats','perl');
 * ```
 *
 * The data of this class is cached.
 * That means if a program is found once it don't have to be searched again.
 *
 * user functions:
 *
 * addPaths() could be used to extend the search paths
 * getCommand() get a command string
 * checkCommand() returns TRUE if a command is available
 *
 * Search paths that are included:
 * $TYPO3_CONF_VARS['GFX']['processor_path']
 * $TYPO3_CONF_VARS['SYS']['binPath']
 * $GLOBALS['_SERVER']['PATH']
 * '/usr/bin/,/usr/local/bin/' on Unix
 *
 * binaries can be preconfigured with
 * $TYPO3_CONF_VARS['SYS']['binSetup']
 */
class CommandUtility
{
    /**
     * Tells if object is already initialized
     */
    protected static bool $initialized = false;

    /**
     * Contains application list. This is an array with the following structure:
     * - app => file name to the application (like 'tar' or 'bzip2')
     * - path => full path to the application without application name (like '/usr/bin/' for '/usr/bin/tar')
     * - valid => TRUE or FALSE
     * Array key is identical to 'app'.
     *
     * @var array<string, array{app: string, path: string, valid: bool}>
     */
    protected static array $applications = [];

    /**
     * Paths where to search for applications
     *
     * The key is a path. The value is either the same path, or false if the path is not valid.
     *
     * @var array<string, string|false>|null
     */
    protected static ?array $paths = null;

    /**
     * Execute a shell command.
     *
     * Needs to be central to have better control and possible fix for issues. Is a wrapper for Symfony's Process
     * component.
     *
     * @see Process
     */
    public static function exec(string|array $command, ?array &$output = null, int &$returnValue = 0): string|false
    {
        if (is_string($command)) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command);
        }

        try {
            $returnValue = $process->run();
        } catch (RuntimeException $runtimeException) {
            self::getLogger()->warning('Executing command "{command}" failed.', [
                'command' => $command,
                'exception' => $runtimeException,
            ]);

            return false;
        }

        $processOutput = $process->getOutput();
        if (str_ends_with($processOutput, PHP_EOL)) {
            // Last \n is ignored by PHP exec(): https://github.com/php/php-src/blob/b675db4c56dd0de4ea1f5195d587ed90f0096ed8/ext/standard/exec.c#L148
            $processOutput = substr($processOutput, 0, -1);
        }
        $output = explode(PHP_EOL, $processOutput);

        return rtrim(strrchr($processOutput, PHP_EOL) ?: $processOutput);

    }

    /**
     * Compile the command for running ImageMagick/GraphicsMagick.
     *
     * @param string $command Command to be run: identify, convert or combine/composite
     * @param string $parameters The parameters string
     * @param string $path Override the default path (e.g. used by the install tool)
     * @return string Compiled command that deals with ImageMagick & GraphicsMagick
     */
    public static function imageMagickCommand(string $command, string $parameters, string $path = ''): string
    {
        $gfxConf = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $isExt = Environment::isWindows() ? '.exe' : '';
        if (!$path) {
            $path = (string)($gfxConf['processor_path'] ?? '');
        }
        $path = GeneralUtility::fixWindowsFilePath($path);
        // This is only used internally, has no effect outside
        if ($command === 'combine') {
            $command = 'composite';
        }
        // Compile the path & command
        if ($gfxConf['processor'] === 'GraphicsMagick') {
            $path = self::escapeShellArgument($path . 'gm' . $isExt) . ' ' . self::escapeShellArgument($command);
        } else {
            if (Environment::isWindows() && !@is_file($path . $command . $isExt)) {
                $path = self::escapeShellArgument($path . 'magick' . $isExt) . ' ' . self::escapeShellArgument($command);
            } else {
                $path = self::escapeShellArgument($path . $command . $isExt);
            }
        }
        // strip profile information for thumbnails and reduce their size
        if ($parameters && $command !== 'identify') {
            // Use legacy processor_stripColorProfileCommand setting if defined, otherwise
            // use the preferred configuration option processor_stripColorProfileParameters
            $stripColorProfileCommand = $gfxConf['processor_stripColorProfileCommand'] ??
                implode(' ', array_map(CommandUtility::escapeShellArgument(...), $gfxConf['processor_stripColorProfileParameters'] ?? []));
            // Determine whether the strip profile action has be disabled by TypoScript:
            if ($gfxConf['processor_stripColorProfileByDefault']
                && $stripColorProfileCommand !== ''
                && $parameters !== '-version'
                && !str_contains($parameters, $stripColorProfileCommand)
                && !str_contains($parameters, '###SkipStripProfile###')
            ) {
                $parameters = $stripColorProfileCommand . ' ' . $parameters;
            } else {
                $parameters = str_replace('###SkipStripProfile###', '', $parameters);
            }

            // When converting images that have background transparency, this needs to be not filled,
            // but preserved, so that e.g. conversion from SVG into PNG/JPG contains transparency info.
            // Without this option, the default background color for conversions is white (https://imagemagick.org/script/command-line-options.php#background)
            $parameters = '-background none ' . $parameters;
        }
        // Add -auto-orient on convert so IM/GM respects the image orient
        if ($parameters && $command === 'convert') {
            $parameters = '-auto-orient ' . $parameters;
        }
        // set interlace parameter for convert command
        if ($command !== 'identify' && $gfxConf['processor_interlace']) {
            $parameters = '-interlace ' . CommandUtility::escapeShellArgument($gfxConf['processor_interlace']) . ' ' . $parameters;
        }
        $cmdLine = $path . ' ' . $parameters;
        // It is needed to change the parameters order when a mask image has been specified
        if ($command === 'composite') {
            $paramsArr = self::unQuoteFilenames($parameters);
            $paramsArrCount = count($paramsArr);
            if ($paramsArrCount > 5) {
                $tmp = $paramsArr[$paramsArrCount - 3];
                $paramsArr[$paramsArrCount - 3] = $paramsArr[$paramsArrCount - 4];
                $paramsArr[$paramsArrCount - 4] = $tmp;
            }
            $cmdLine = $path . ' ' . implode(' ', $paramsArr);
        }
        return $cmdLine;
    }

    /**
     * Checks if a command is valid or not, updates global variables
     *
     * @param string $cmd The command that should be executed. eg: "convert"
     * @param string $handler Executor for the command. eg: "perl"
     * @return bool|int True if the command is valid; False if cmd is not found; -1 if the handler is not found
     */
    public static function checkCommand(string $cmd, string $handler = ''): bool|int
    {
        if (!self::init()) {
            return false;
        }

        if ($handler !== '' && !self::checkCommand($handler)) {
            return -1;
        }
        // Already checked and valid
        if (self::$applications[$cmd]['valid'] ?? false) {
            return true;
        }
        // Is set but was (above) not TRUE
        if (isset(self::$applications[$cmd]['valid'])) {
            return false;
        }

        foreach (self::$paths as $path => $validPath) {
            // Ignore invalid (FALSE) paths
            if ($validPath) {
                if (Environment::isWindows()) {
                    // Windows OS
                    // @todo Why is_executable() is not called here?
                    if (@is_file($path . $cmd)) {
                        self::$applications[$cmd]['app'] = $cmd;
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                    if (@is_file($path . $cmd . '.exe')) {
                        self::$applications[$cmd]['app'] = $cmd . '.exe';
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                } else {
                    // Unix-like OS
                    $filePath = realpath($path . $cmd);
                    if ($filePath && @is_executable($filePath)) {
                        self::$applications[$cmd]['app'] = $cmd;
                        self::$applications[$cmd]['path'] = $path;
                        self::$applications[$cmd]['valid'] = true;
                        return true;
                    }
                }
            }
        }

        // Try to get the executable with the command 'which'.
        // It does the same like already done, but maybe on other paths
        if (!Environment::isWindows()) {
            $output = null;
            $returnValue = 0;
            $cmd = @self::exec('which ' . self::escapeShellArgument($cmd), $output, $returnValue);

            if ($returnValue === 0) {
                self::$applications[$cmd]['app'] = $cmd;
                self::$applications[$cmd]['path'] = PathUtility::dirname($cmd) . '/';
                self::$applications[$cmd]['valid'] = true;
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a command string for exec(), system()
     *
     * @param string $cmd The command that should be executed. eg: "convert"
     * @param string $handler Handler (executor) for the command. eg: "perl"
     * @param string $handlerOpt Options for the handler, like '-w' for "perl"
     * @return string|bool|int Returns command string, or FALSE if cmd is not found, or -1 if the handler is not found
     */
    public static function getCommand(string $cmd, string $handler = '', string $handlerOpt = ''): string|bool|int
    {
        if (!self::init()) {
            return false;
        }

        // Handler
        if ($handler) {
            $handler = self::getCommand($handler);

            if (!$handler) {
                return -1;
            }
            $handler .= ' ' . escapeshellcmd($handlerOpt) . ' ';
        }

        // Command
        if (!self::checkCommand($cmd)) {
            return false;
        }
        $cmd = self::$applications[$cmd]['path'] . self::$applications[$cmd]['app'] . ' ';

        return trim($handler . $cmd);
    }

    /**
     * Extend the preset paths. This way an extension can install an executable and provide the path to \TYPO3\CMS\Core\Utility\CommandUtility
     *
     * @param string $paths Comma separated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with public web path
     */
    public static function addPaths(string $paths): void
    {
        self::initPaths($paths);
    }

    /**
     * Returns an array of search paths
     *
     * @param bool $addInvalid If set the array contains invalid path too. Then the key is the path and the value is empty
     * @return array<string, string|false> Array of search paths (empty if exec is disabled)
     */
    public static function getPaths(bool $addInvalid = false): array
    {
        if (!self::init()) {
            return [];
        }

        return $addInvalid
            ? self::$paths
            : array_filter(self::$paths);
    }

    /**
     * Initializes this class
     */
    protected static function init(): bool
    {
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']) {
            return false;
        }
        if (!self::$initialized) {
            self::initPaths();
            self::$applications = self::getConfiguredApps();
            self::$initialized = true;
        }
        return true;
    }

    /**
     * Initializes and extends the preset paths with own
     *
     * @param string $paths Comma separated list of extra paths where a command should be searched. Relative paths (without leading "/") are prepend with public web path
     */
    protected static function initPaths(string $paths = ''): void
    {
        $doCheck = false;

        // Init global paths array if not already done
        if (!is_array(self::$paths)) {
            self::$paths = self::getPathsInternal();
            $doCheck = true;
        }
        // Merge the submitted paths array to the global
        if ($paths) {
            $paths = GeneralUtility::trimExplode(',', $paths, true);
            foreach ($paths as $path) {
                // Make absolute path of relative
                if (!str_starts_with($path, '/')) {
                    $path = Environment::getPublicPath() . '/' . $path;
                }
                if (!isset(self::$paths[$path])) {
                    if (@is_dir($path)) {
                        self::$paths[$path] = $path;
                    } else {
                        self::$paths[$path] = false;
                    }
                }
            }
        }
        // Check if new paths are invalid
        if ($doCheck) {
            foreach (self::$paths as $path => $valid) {
                // Ignore invalid (FALSE) paths
                if ($valid && !@is_dir($path)) {
                    self::$paths[$path] = false;
                }
            }
        }
    }

    /**
     * Processes and returns the paths from $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']
     *
     * @return array<string, array{app: string, path: string, valid: bool}> Array of commands and path
     */
    protected static function getConfiguredApps(): array
    {
        $cmdArr = [];

        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']) {
            $binSetup = str_replace(['\'.chr(10).\'', '\' . LF . \''], LF, $GLOBALS['TYPO3_CONF_VARS']['SYS']['binSetup']);
            $pathSetup = preg_split('/[\n,]+/', $binSetup);
            foreach ($pathSetup as $val) {
                if (trim($val) === '') {
                    continue;
                }
                [$cmd, $cmdPath] = GeneralUtility::trimExplode('=', $val, true, 2);
                $cmdArr[$cmd]['app'] = PathUtility::basename($cmdPath);
                $cmdArr[$cmd]['path'] = PathUtility::dirname($cmdPath) . '/';
                $cmdArr[$cmd]['valid'] = true;
            }
        }

        return $cmdArr;
    }

    /**
     * Sets the search paths from different sources, internal
     *
     * @return array<string, string> Array of absolute paths (keys and values are equal)
     */
    protected static function getPathsInternal(): array
    {
        $pathsArr = [];
        $sysPathArr = [];

        // Image magick paths first
        if ($imPath = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path']) {
            $imPath = self::fixPath($imPath);
            $pathsArr[$imPath] = $imPath;
        }

        // Add configured paths
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath']) {
            $sysPath = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['binPath'], true);
            foreach ($sysPath as $val) {
                $val = self::fixPath($val);
                $sysPathArr[$val] = $val;
            }
        }

        // Add path from environment
        if (!empty($GLOBALS['_SERVER']['PATH']) || !empty($GLOBALS['_SERVER']['Path'])) {
            $sep = Environment::isWindows() ? ';' : ':';
            $serverPath = $GLOBALS['_SERVER']['PATH'] ?? $GLOBALS['_SERVER']['Path'];
            $envPath = GeneralUtility::trimExplode($sep, $serverPath, true);
            foreach ($envPath as $val) {
                $val = self::fixPath($val);
                $sysPathArr[$val] = $val;
            }
        }

        // Set common paths for Unix (only)
        if (!Environment::isWindows()) {
            $sysPathArr = array_merge($sysPathArr, [
                '/usr/bin/' => '/usr/bin/',
                '/usr/local/bin/' => '/usr/local/bin/',
            ]);
        }

        return array_merge($pathsArr, $sysPathArr);
    }

    /**
     * Set a path to the right format
     *
     * @param string $path Input path
     * @return string Output path
     */
    protected static function fixPath(string $path): string
    {
        return str_replace('//', '/', $path . '/');
    }

    /**
     * Escape shell arguments (for example filenames) to be used on the local system.
     *
     * The setting UTF8filesystem will be taken into account.
     *
     * @param string[] $input Input arguments to be escaped
     * @return string[] Escaped shell arguments
     */
    public static function escapeShellArguments(array $input): array
    {
        $isUTF8Filesystem = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']);
        $currentLocale = false;
        if ($isUTF8Filesystem) {
            if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale'] ?? false) {
                $currentLocale = setlocale(LC_CTYPE, '0');
                setlocale(LC_CTYPE, $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemLocale']);
            }
        }

        $output = array_map('escapeshellarg', $input);

        if ($isUTF8Filesystem && $currentLocale !== false) {
            setlocale(LC_CTYPE, $currentLocale);
        }

        return $output;
    }

    /**
     * Explode a string (normally a list of filenames) with whitespaces by considering quotes in that string.
     *
     * @param string $parameters The whole parameters string
     * @return array Exploded parameters
     */
    protected static function unQuoteFilenames(string $parameters): array
    {
        $paramsArr = explode(' ', trim($parameters));
        // Whenever a quote character (") is found, $quoteActive is set to the element number inside of $params.
        // A value of -1 means that there are not open quotes at the current position.
        $quoteActive = -1;
        foreach ($paramsArr as $k => $v) {
            if ($quoteActive > -1) {
                $paramsArr[$quoteActive] .= ' ' . $v;
                unset($paramsArr[$k]);
                if (substr($v, -1) === $paramsArr[$quoteActive][0]) {
                    $quoteActive = -1;
                }
            } elseif (!trim($v)) {
                // Remove empty elements
                unset($paramsArr[$k]);
            } elseif (preg_match('/^(["\'])/', $v) && substr($v, -1) !== $v[0]) {
                $quoteActive = $k;
            }
        }
        // Return re-indexed array
        return array_values($paramsArr);
    }

    /**
     * Escape a shell argument (for example a filename) to be used on the local system.
     *
     * The setting UTF8filesystem will be taken into account.
     *
     * @param string $input Input-argument to be escaped
     * @return string Escaped shell argument
     */
    public static function escapeShellArgument(string $input): string
    {
        return self::escapeShellArguments([$input])[0];
    }

    protected static function getLogger(): LoggerInterface
    {
        return GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }
}
