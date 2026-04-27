<?php

declare(strict_types=1);

namespace App\Inventory\Infrastructure;

use App\Inventory\Domain\Facade\InventoryLabelImageGenerator;
use RuntimeException;
use Symfony\Component\Process\Process;

final readonly class PythonInventoryLabelImageGenerator implements InventoryLabelImageGenerator
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function generate(string $publicCode): string
    {
        $payload = json_encode([
            'publicCode' => $publicCode,
            'logoPath' => $this->logoPath(),
        ], \JSON_THROW_ON_ERROR);
        $process = new Process(['python3', $this->scriptPath()]);
        $process->setTimeout(30.0);
        $process->setInput($payload);
        $process->run();
        if ($process->isSuccessful()) {
            return $process->getOutput();
        }
        $message = trim($process->getErrorOutput().$process->getOutput());

        throw new RuntimeException('' !== $message ? $message : 'Inventory label image generation failed.');
    }

    private function scriptPath(): string
    {
        $path = $this->projectDir.'/bin/inventory_labels/generate_label_image.py';
        if (!is_file($path)) {
            throw new RuntimeException('Inventory label image generator script not found.');
        }

        return $path;
    }

    private function logoPath(): string
    {
        $candidates = [
            $this->projectDir.'/../frontend/public/logo.png',
            $this->projectDir.'/spa/logo.png',
        ];
        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}
