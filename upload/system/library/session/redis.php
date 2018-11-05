<?php
namespace Session;

final class Redis
{
	protected $prefix = 'sess_';

	private $expire = '';

	private $use_cache;

	/**
	 * Redis constructor.
	 * @param \Registry $registry
	 * @throws \Exception
	 */
	public function __construct(\Registry $registry)
	{
		$cache = $registry->get('cache');

		if($cache && $cache->getId() === 'redis'){

			$this->redis = $cache;

			$this->use_cache = true;

		} else {

			$this->redis = new \Redis();

			if (!$this->redis->pconnect('127.0.0.1')) {
				throw new \Exception("Permissions denied session storage");
			}

			$this->use_cache = false;

			$this->expire = ini_get('session.gc_maxlifetime');
		}
	}

	/**
	 * @param $session_id
	 * @return bool|mixed
	 */
	public function read($session_id)
	{
		if($this->use_cache){

			$result = $this->redis->get($this->prefix . $session_id);

			if ($result !== null) {
				return json_decode( $result, true );
			}

		} else {

			if ($this->redis->exists($this->prefix . $session_id)) {
				return json_decode( $this->redis->get($this->prefix . $session_id), true );
			}
		}

		return false;
	}

	/**
	 * @param $session_id
	 * @param $data
	 * @return bool
	 */
	public function write($session_id, $data)
	{

		if ($session_id) {

			if($this->use_cache){

				$this->redis->set($this->prefix . $session_id, json_encode($data));

			} else {

				$this->redis->psetex($this->prefix . $session_id, $this->expire, json_encode($data));
			}
		}

		return true;
	}

	/**
	 * @param $session_id
	 * @return int
	 */
	public function destroy($session_id)
	{

		if($this->use_cache){

			$this->redis->delete($this->prefix . $session_id);

		} else {

			if ($this->redis->exists($this->prefix . $session_id)) {
				$this->redis->delete($this->prefix . $session_id);
			}
		}

		return true;
	}

	/**
	 * @param $expire
	 * @return bool
	 */
	public function gc($expire) {
		return true;
	}

}