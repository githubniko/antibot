<?php
namespace DnsCache;

interface CacheDriverInterface {
    /**
     * Получить данные из кэша
     * @param string $key
     * @return array|null
     */
    public function get($key);

    /**
     * Записать данные в кэш
     * @param string $key
     * @param array $data
     * @param int $ttl
     * @return bool
     */
    public function set($key, array $data, $ttl);

    /**
     * Удалить данные из кэша
     * @param string $key
     * @return bool
     */
    public function delete($key);

    /**
     * Очистить весь кэш
     * @return bool
     */
    public function clear();

    /**
     * Проверить доступность драйвера
     * @return bool
     */
    public function isAvailable();

    /**
     * @param string $ip
     * @param string|null $hostname null для негативных ответов
     * @param int $ttl
     * @return bool
     */
    public function setReverseDns($ip, $hostname, $ttl);
    
    /**
     * @param string $ip
     * @return string|false|null 
     *   string - найденный hostname
     *   null - негативный ответ (hostname не найден)
     *   false - запись отсутствует в кэше
     */
    public function getReverseDns($ip);

}