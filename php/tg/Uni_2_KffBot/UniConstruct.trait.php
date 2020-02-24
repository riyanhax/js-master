<?php

/**
 *
 */
trait UniConstruct
{
	protected
		$cmd, $urlROOT, $urlDIR;

	private function import(UniKffBot &$UKB)
	{
		foreach (get_object_vars($UKB) as $key=> &$value)
		{
			$this->$key = $value;
		}
		// $this->log->add(__METHOD__.'',null,[get_object_vars($UKB)]);
	}


	private function setConstruct(UniKffBot &$UKB, ?array $cmdArr=null)
	{
		$this->UKB = $UKB;
		$this->import($UKB);

		//* Define folders
		$this->urlROOT = ((!empty($_SERVER['HTTPS'])) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
		$this->urlDIR = $this->urlROOT . '/' . str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);



		$this->log->add(__METHOD__.' $cmdArr=',null,[$cmdArr]);

		$this->cmd = [array_shift($cmdArr), $cmdArr];

		$this->log->add(__METHOD__.' owner, $this->cmd=',null,[[$this->is_owner, $this->get('is_owner'), $this->cbn['from']['id']], $this->cmd]);

		//* Define tokens
		if(empty($this->tokens[$this->apiName ?? strtolower(__CLASS__)]))
		{
			$this->log->add(__METHOD__ . '$this->tokens = ', E_USER_WARNING, [$this->tokens]);
		}
		return $this;
	}

} //* UniConstruct
