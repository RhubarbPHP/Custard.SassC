<?php

/*
 *	Copyright 2016 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Custard\SassC;

use Rhubarb\Crown\String\StringTools;
use Rhubarb\Custard\Command\CustardCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CompileScssCommand extends CustardCommand
{
    public function __construct()
    {
        parent::__construct('compile:scss');

        $this->addOption('style', 's', InputOption::VALUE_REQUIRED, 'Output style. Can be: nested, compressed.', 'compressed');
        $this->addOption('line-numbers', 'l', InputOption::VALUE_NONE, 'Emit comments showing original line numbers.');
        $this->addOption('import-path', 'i', InputOption::VALUE_REQUIRED, 'Set Sass import path.');
        $this->addOption('sourcemap', 'm', InputOption::VALUE_NONE, 'Emit source map.');
        $this->addOption('omit-map-comment', 'M', InputOption::VALUE_NONE, 'Omits the source map url comment.');
        $this->addOption('precision', 'p', InputOption::VALUE_REQUIRED, 'Set the precision for numbers.');
        $this->addOption('autoprefix', 'a', InputOption::VALUE_NONE, 'Run postcss autoprefixer on output CSS files.');

        $this->addArgument('input', null, 'File or directory to compile');
        $this->addArgument('output', null, 'File or directory to output to');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $inputPath = $input->getArgument('input');
        if ($inputPath == null) {
            $inputPath = $this->askQuestion('What file or directory do you want to compile?', null, true);
        }
        $inputPath = str_replace('\\', '/', $inputPath);
        $inputPath = rtrim($inputPath, '/');

        if (!file_exists($inputPath)) {
            $this->writeNormal('The input path specified does not exist.', true);
            return 1;
        }

        $multiple = false;

        if (is_dir($inputPath)) {
            $multiple = true;

            $scssFiles = $this->scanDirectoryForScssFiles($inputPath);

            if (!count($scssFiles)) {
                $this->writeNormal('The input directory does not contain any .css files.', true);
                return 1;
            }
        } else {
            $scssFiles = [$inputPath];
        }

        $outputPath = $input->getArgument('output');
        if ($outputPath == null) {
            $outputPath = $this->askQuestion('What file or directory do you want to output to?', null, true);
        }
        $outputPath = str_replace('\\', '/', $outputPath);
        $outputPath = rtrim($outputPath, '/');

        if (!file_exists($outputPath)) {
            if (pathinfo($outputPath, PATHINFO_EXTENSION) != null) {
                // $outputPath has an extension, so assume it's a file path and check its parent is a directory
                $outputDir = pathinfo($outputPath, PATHINFO_DIRNAME);
                if (!file_exists($outputDir)) {
                    $this->writeNormal("The output path $outputDir does not exist.", true);
                    return 1;
                } else if (!is_dir($outputDir)) {
                    $this->writeNormal("The output path $outputDir is not a directory.", true);
                    return 1;
                }
            } else {
                $this->writeNormal("The output path $outputPath does not exist.", true);
                return 1;
            }
        }

        if ($multiple && !is_dir($outputPath)) {
            $this->writeNormal('The input path was a directory so output path must also be a directory.', true);
            return 1;
        }

        return $this->compileScssFiles($scssFiles, $multiple, $outputPath);
    }

    protected function getOptionsForSassC()
    {
        $allowedOptions = array_flip(['style', 'line-numbers', 'import-path', 'sourcemap', 'omit-map-comment', 'precision']);
        $specifiedOptions = array_intersect_key($this->input->getOptions(), $allowedOptions);

        $options = [];
        foreach ($specifiedOptions as $name => $value) {
            if ($value === true) {
                $options[] = "--$name";
            } elseif (is_string($value)) {
                $options[] = "--$name $value";
            }
        }

        return implode(' ', $options);
    }

    /**
     * @param string $directoryPath
     * @return string[] SCSS file paths
     */
    protected function scanDirectoryForScssFiles($directoryPath)
    {
        $dirHandle = opendir($directoryPath);
        $scssFiles = [];
        while (($fileName = readdir($dirHandle)) !== false) {
            if (!StringTools::startsWith($fileName, '_') && StringTools::endsWith($fileName, '.scss')) {
                $scssFiles[] = $directoryPath . '/' . $fileName;
            }
        }
        closedir($dirHandle);
        return $scssFiles;
    }

    /**
     * @param string[] $scssFiles
     * @param bool $inputIsDirectory
     * @param string $outputPath
     * @return int Status code
     */
    protected function compileScssFiles($scssFiles, $inputIsDirectory, $outputPath)
    {
        $cssFilePath = $outputPath;
        if (!$inputIsDirectory && is_dir($outputPath)) {
            $cssFilePath = $outputPath . '/' . pathinfo($scssFiles[0], PATHINFO_FILENAME) . '.css';
        }

        $status = 0;

        foreach ($scssFiles as $scssFilePath) {
            if ($inputIsDirectory) {
                $cssFilePath = $outputPath . '/' . pathinfo($scssFilePath, PATHINFO_FILENAME) . '.css';
            }

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $exe = 'sassc.exe';
            } elseif (strtoupper(PHP_OS) === 'DARWIN') {
                $exe = 'sassc.macosx';
            } else {
                $exe = 'sassc';
            }

            exec(VENDOR_DIR . "/eslider/sasscb/dist/$exe {$this->getOptionsForSassC()} $scssFilePath $cssFilePath 2>&1", $cliOutput, $returnStatus);

            $cliOutput = trim(implode("\n", $cliOutput));
            if ($returnStatus) {
                $this->writeNormal("<error>Compiling $scssFilePath failed</error>", true);
                if ($cliOutput) {
                    $this->writeNormal("<comment>$cliOutput</comment>", true);
                }
            } else {
                if ($cliOutput) {
                    $this->writeVerbose($cliOutput, true);
                }
                $this->writeNormal("<info>$scssFilePath compiled to $cssFilePath</info>", true);

                if ($this->input->getOption('autoprefix')) {
                    $returnStatus = $this->runAutoPrefixer($cssFilePath);
                }
            }

            if ($returnStatus) {
                $status = $returnStatus;
            }
        }

        return $status;
    }

    protected function runAutoPrefixer($cssFile)
    {
        exec("postcss --use autoprefixer $cssFile 2>&1 -o $cssFile", $cliOutput, $returnStatus);

        $cliOutput = trim(implode("\n", $cliOutput));
        if ($returnStatus) {
            $this->writeNormal("<error>Autoprefixing $cssFile failed</error>", true);
            if ($cliOutput) {
                $this->writeNormal("<comment>$cliOutput</comment>", true);
            }
        } else {
            if ($cliOutput) {
                $this->writeVerbose($cliOutput, true);
            }
            $this->writeNormal("<info>$cssFile autoprefixed</info>", true);
        }

        return $returnStatus;
    }
}
