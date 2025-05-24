<?php

namespace DnsCache;

class DnsCache
{
    private $driver;
    private $defaultTtl;
    private $maxTtl;

    public function __construct(
        CacheDriverInterface $driver,
        $defaultTtl = 3600,
        $maxTtl = 86400
    ) {
        $this->driver = $driver;
        $this->defaultTtl = $defaultTtl;
        $this->maxTtl = $maxTtl;

        if (!$this->driver->isAvailable()) {
            throw new \RuntimeException('Cache driver is not available');
        }
    }

    public function getRecord($host, $type = DNS_A)
    {
        $key = $this->generateKey($host, $type);
        $cached = $this->driver->get($key);

        if ($cached !== false) {  // false означает отсутствие записи
            return $cached['records'];
        }

        $records = dns_get_record($host, $type);
        if ($records === false) {
            throw new \RuntimeException("DNS query failed for {$host}");
        }

        $ttl = $this->calculateTtl($records, $type);
        $this->driver->set($key, [
            'records' => $records,
            'ttl' => $ttl
        ], min($ttl, $this->maxTtl));

        return $records;
    }

    private function generateKey($host, $type)
    {
        return md5($host . $type);
    }

    private function calculateTtl($records, $type)
    {
        if (empty($records)) return $this->defaultTtl;

        $ttls = array();
        foreach ($records as $record) {
            if (isset($record['ttl'])) $ttls[] = $record['ttl'];
        }

        return !empty($ttls) ? min($ttls) : $this->defaultTtl;
    }

    /**
     * Кэшированная версия gethostbyaddr()
     * @param string $ip IP-адрес
     * @param int $negativeTtl TTL для "негативных" ответов (когда возвращается IP)
     * @param int $positiveTtl TTL для "позитивных" ответов
     * @return string
     */
    public function getHostByAddr($ip, $negativeTtl = 3600, $positiveTtl = 86400)
    {
        // Проверяем кэш
        $cached = $this->driver->getReverseDns($ip);
        if ($cached !== false) {  // false означает отсутствие записи
            return $cached === null ? $ip : $cached;
        }

        // Делаем запрос
        $hostname = gethostbyaddr($ip);

        // Определяем параметры кэширования
        $isNegative = ($hostname === $ip);
        $ttl = $isNegative ? $negativeTtl : $positiveTtl;

        // Сохраняем в кэш (для негативных ответов сохраняем null)
        $this->driver->setReverseDns($ip, $isNegative ? null : $hostname, $ttl);

        return $hostname;
    }
}
