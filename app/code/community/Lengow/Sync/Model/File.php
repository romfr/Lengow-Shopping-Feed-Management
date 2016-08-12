<?php

class Lengow_Sync_Model_File extends Varien_Io_File
{
	/**
	 * Override adding the LOCK_NB option
	 * @param bool $exclusive
	 * @return bool
	 */
	public function streamLock($exclusive = true)
    {
        if (!$this->_streamHandler) {
            return false;
        }
        $this->_streamLocked = true;
        $lock = $exclusive ? LOCK_EX : LOCK_SH;
        return flock($this->_streamHandler, $lock | LOCK_NB);
    }

    public function streamErase()
    {
        if (!$this->_streamHandler) {
            return false;
        }
        return ftruncate($this->_streamHandler, 0);
    }
}