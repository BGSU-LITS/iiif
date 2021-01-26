<?php

declare(strict_types=1);

namespace Lits\Command;

use GetOpt\GetOpt;
use GetOpt\Operand;
use GetOpt\Option;
use Lits\Command;
use Lits\Config\PresentationConfig;
use Lits\Data\CsvData;

final class ProcessCommand extends Command
{
    public function command(): void
    {
        $this->getopt->addOperand(
            Operand::create('file', Operand::REQUIRED)
                ->setDescription('CSV file to process.')
        );

        $this->getopt->addOption(
            Option::create('k', 'key', GetOpt::REQUIRED_ARGUMENT)
                ->setArgumentName('key')
                ->setDescription('10-digit hexadecimal access key.')
        );

        $this->getopt->addOption(
            Option::create('e', 'ext', GetOpt::REQUIRED_ARGUMENT)
                ->setArgumentName('extension')
                ->setDescription('File extension without any leading dot.')
        );

        $this->getopt->addOption(
            Option::create('s', 'size', GetOpt::REQUIRED_ARGUMENT)
                ->setArgumentName('size')
                ->setDescription('Size codeword.')
        );

        if (!$this->process()) {
            return;
        }

        \assert($this->settings['presentation'] instanceof PresentationConfig);

        $options = $this->getopt->getOptions();

        if (isset($options['key'])) {
            $this->settings['presentation']->key = (string) $options['key'];
        }

        if (isset($options['ext'])) {
            $this->settings['presentation']->ext = (string) $options['ext'];
        }

        if (isset($options['size'])) {
            $this->settings['presentation']->size = (string) $options['size'];
        }

        $csv = new CsvData($this->settings);
        $csv->load((string) $this->getopt->getOperand('file'));

        foreach ($csv->presentations as $presentation) {
            $presentation->save();
        }
    }
}
