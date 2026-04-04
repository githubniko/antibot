<?php
namespace Cache;

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
     * @return bool
     */
    public function set($key, array $data);

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

}