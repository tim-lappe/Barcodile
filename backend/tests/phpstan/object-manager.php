<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$projectDir = dirname(__DIR__, 2);
if (is_file($projectDir.'/.env')) {
    (new Dotenv())->bootEnv($projectDir.'/.env');
}

$kernel = new Kernel('dev', false);
$kernel->boot();

$doctrine = $kernel->getContainer()->get('doctrine');

$manager = $doctrine->getManager();
if (!$manager instanceof EntityManagerInterface) {
    throw new LogicException('Doctrine default entity manager must be configured.');
}

return $manager;
