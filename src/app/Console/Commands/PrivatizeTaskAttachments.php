<?php

namespace App\Console\Commands;

use App\Models\Todo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PrivatizeTaskAttachments extends Command
{
    protected $signature = 'tasks:privatize-attachments {--dry-run : Report changes without writing or deleting files}';

    protected $description = 'Move legacy public task attachments to private storage without changing database paths';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $paths = array_unique(array_merge(
            $public->allFiles('todo-files'),
            Todo::whereNotNull('file_path')->pluck('file_path')->all(),
        ));
        $failures = 0;

        foreach ($paths as $path) {
            $temporary = $path.'.migrating';

            try {
                if (!str_starts_with($path, 'todo-files/') || str_contains($path, '..')) {
                    throw new \RuntimeException('Unexpected attachment path.');
                }

                if (!$public->exists($path)) {
                    if (!$private->exists($path)) {
                        throw new \RuntimeException('Attachment is missing from both disks.');
                    }
                    $this->line("Already private: {$path}");
                    continue;
                }

                $checksum = $this->checksum($public->path($path));
                if ($private->exists($path) && $this->checksum($private->path($path)) !== $checksum) {
                    throw new \RuntimeException('Private attachment differs; neither copy was changed.');
                }

                if ($this->option('dry-run')) {
                    $this->line("Would privatize: {$path}");
                    continue;
                }

                if (!$private->exists($path)) {
                    $stream = $public->readStream($path);
                    if (!is_resource($stream)) {
                        throw new \RuntimeException('Unable to read public attachment.');
                    }
                    try {
                        if (!$private->put($temporary, $stream)) {
                            throw new \RuntimeException('Unable to copy attachment.');
                        }
                    } finally {
                        fclose($stream);
                    }

                    if ($this->checksum($private->path($temporary)) !== $checksum) {
                        throw new \RuntimeException('Copied attachment failed checksum verification.');
                    }
                    if (!$private->move($temporary, $path)) {
                        throw new \RuntimeException('Unable to finalize private attachment.');
                    }
                }

                if ($this->checksum($private->path($path)) !== $this->checksum($public->path($path))) {
                    throw new \RuntimeException('Attachment changed during migration; public copy retained.');
                }
                if (!$public->delete($path)) {
                    throw new \RuntimeException('Unable to remove verified public attachment.');
                }

                $this->line("Privatized: {$path}");
            } catch (\Throwable $e) {
                $failures++;
                $this->error("{$path}: {$e->getMessage()}");
            }
        }

        return $failures ? self::FAILURE : self::SUCCESS;
    }

    private function checksum(string $path): string
    {
        $checksum = hash_file('sha256', $path);
        if ($checksum === false) {
            throw new \RuntimeException('Unable to checksum attachment.');
        }

        return $checksum;
    }
}
