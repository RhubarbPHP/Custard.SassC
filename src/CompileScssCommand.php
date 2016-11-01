<?php

namespace Rhubarb\Custard\SassC;

use Rhubarb\Crown\String\StringTools;
use Rhubarb\Custard\Command\CustardCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompileScssCommand extends CustardCommand
{
    public function __construct()
    {
        parent::__construct('compile:scss');

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
            return;
        }

        $multiple = false;

        if (is_dir($inputPath)) {
            $multiple = true;

            $scssFiles = $this->scanDirectoryForScssFiles($inputPath);

            if (!count($scssFiles)) {
                $this->writeNormal('The input directory does not contain any .css files.', true);
                return;
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
            $this->writeNormal('The output path specified does not exist.', true);
            return;
        }

        if ($multiple && !is_dir($outputPath)) {
            $this->writeNormal('The input path was a directory so output path must also be a directory.', true);
            return;
        }

        $this->compileScssFiles($scssFiles, $multiple, $outputPath);
    }

    /**
     * @param string $directoryPath
     * @return string[] SCSS file paths
     */
    protected function scanDirectoryForScssFiles($directoryPath)
    {
        $dirHandle = opendir($directoryPath);
        $scssFiles = [];
        while ($fileName = readdir($dirHandle)) {
            if (!StringTools::startsWith($fileName, '_') && StringTools::endsWith($fileName, '.scss')) {
                $scssFiles[] = $directoryPath . $fileName;
            }
        }
        closedir($dirHandle);
        return $scssFiles;
    }

    /**
     * @param string[] $scssFiles
     * @param bool $inputIsDirectory
     * @param string $outputPath
     */
    protected function compileScssFiles($scssFiles, $inputIsDirectory, $outputPath)
    {
        $cssFilePath = '';
        if (!$inputIsDirectory && is_dir($outputPath)) {
            $cssFilePath = $outputPath . '/' . pathinfo($scssFiles[0], PATHINFO_FILENAME) . '.css';
        }

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

            exec(VENDOR_DIR . '/eslider/sasscb/dist/' . $exe . ' ' . $scssFilePath . ' ' . $cssFilePath, $cliOutput);

            $this->writeVerbose(implode("\n", $cliOutput), true);
            $this->writeNormal($scssFilePath . ' compiled to ' . $cssFilePath, true);
        }
    }
}
