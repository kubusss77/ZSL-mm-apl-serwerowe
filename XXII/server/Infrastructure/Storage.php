<?php

/** Udostępnia prostą warstwę dostępu do Memcached używaną przez grę */
class Storage {
  private Memcached $memcached;

  /** Łączy aplikację z lokalną instancją Memcached */
  public function __construct() {
    $this->memcached = new Memcached();
    $this->memcached->addServer('127.0.0.1', 11211);
  }

  /** Pobiera wartość spod klucza albo zwraca null, gdy wpis nie istnieje */
  public function get(string $key) {
    $value = $this->memcached->get($key);
    return $this->memcached->getResultCode() === Memcached::RES_SUCCESS ? $value : null;
  }

  /** Zapisuje wartość z opcjonalnym czasem ważności. */
  public function set(string $key, $value, int $expiration = 0): bool {
    return $this->memcached->set($key, $value, $expiration);
  }

  /** Dodaje wartość tylko wtedy, gdy klucz jest jeszcze wolny */
  public function add(string $key, $value, int $expiration = 0): bool {
    return $this->memcached->add($key, $value, $expiration);
  }

  /** Zwiększa licznik i inicjalizuje go, jeśli wcześniej nie istniał */
  public function increment(string $key, int $offset = 1, int $initial = 0): int {
    $result = $this->memcached->increment($key, $offset);
    if ($this->memcached->getResultCode() === Memcached::RES_SUCCESS) {
      return (int) $result;
    }
    $this->memcached->set($key, $initial + $offset);
    return $initial + $offset;
  }

  /** Usuwa wpis spod podanego klucza */
  public function delete(string $key): bool {
    return $this->memcached->delete($key);
  }
}
