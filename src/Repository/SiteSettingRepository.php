<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SiteSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteSetting>
 */
class SiteSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteSetting::class);
    }

    public function findByKey(string $key): ?SiteSetting
    {
        return $this->findOneBy(['settingKey' => $key]);
    }

    public function getValue(string $key, string $default = ''): string
    {
        $setting = $this->findByKey($key);

        return $setting?->getSettingValue() ?? $default;
    }
}
