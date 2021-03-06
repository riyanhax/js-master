<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once "../CommonBot.class.php";


class AntimatKFF extends CommonBot implements iBotTG
{
	protected
		# Test mode, bool
		$__test = 1 ,
		// $token = '',

		$maxTry = 3,
		$protecText = "Вы пытаетесь воспользоваться частным ботом.\nДля его разблокировки свяжитесь с автором *@korniloff75*";


	protected static
		$patterns = [
			'~(?:\\s|^|>|\\]).{0,4}?[хx][\\s_]*?[уy](?![бж])[\\s_]*?[ийеёюя](?![з])~iu',
			'~п(?![ор]).?[еёиі].{0,2}?[зz3].{0,2}?д[а@]?~iu',
			'~(?:[^аеор]|\\s|^)[бм]и?ля[дт]ь?|п[еи][дg][ао]?р~iu',
			'~г[ао]вно?|г[оа]ндон|жоп[аеу]|[^о]мандав?[^лрт]|\\bа\\.?у\\.?е\\.?~iu',
			'~(?:[^вджл-нр-тч-щ]|^|\\s)[ьъ]?[еёїє]б\\W*?[^ы\\s]~iu',
			'~сра[лт]ь?|залупа?|дроч~iu',
			// фразы
			'~сос[иу] (?:член|хуй|хер)|(?:член|хуй|хер) сос[иу]~iu',
			# Test
			// '~123~',
		];


	public function __construct()
	{
		//* Set local data
		$this->botFileInfo = new kffFileInfo(__FILE__);

		/* Запускаем скрипт
		 * Protect from CommonBot
		 * $this->checkLicense();
		*/
		parent::__construct()->checkLicense()->init();

	} //__construct

	/**
	 *
	 */
	protected function init()
	{
		if(empty($this->inputData) || !$this->message)
		{
			$this->log->add('Нет входящего запроса');
			die ('Нет входящего запроса');
		}
		else $this->log->add('$this->message', null, [$this->message]);

		$text = $this->message['text'];
		$censure = preg_replace(self::$patterns, " <b>[<a href='https://js-master.ru/content/5.Razrabotki/Antimat_plus/'>цензура</a>]</b> ", $text);

		//* Мата нет
		if(!strcmp($text, $censure))
		{
			$this->log->add("censure FAIL", null, [$text, $censure]);
			return;
		}
		else
		{
			$this->log->add("censure SUCCESS");
		}

		$user = $this->message['from']['username'];
		$base = \H::json('base.json');
		$base[$user] = $base[$user] ?? ['count'=>0];
		$this->log->add(__METHOD__ . '$base[$user] = ', null, [$base[$user]]);

		if(
			is_numeric($base[$user]['count'])
			&& ++$base[$user]['count'] > $this->maxTry
		)
		{
			$censure = "Всё, пиздец тебе, <b>@{$user}</b>!\n\nВсе твои посты с матом впредь будут удаляться. Переходи на литературный язык.";
		}
		else
		{
			$censure = "Отредактировано сообщение от @{$user}\n\n$censure\n\nПользователю <b>@{$user}</b> выдано <b>{$base[$user]['count']}</b>-е предупреждение от администрации.";

		}

		//* Отключаем предпросмотр ссылок
		$this->responseData['disable_web_page_preview'] = true;

		if(
			!is_numeric($base[$user]['count'])
			|| $base[$user]['count'] <= ($this->maxTry + 1)
		)
		{
			\H::json('base.json', $base);
			$this->responseData['text'] = $censure;
			$this->log->add("apiRequest = ", null, [$this->apiRequest($this->responseData)]);
		}

		$this->log->add("apiResponseJSON = ", null, [$this->apiResponseJSON($this->responseData, 'deleteMessage')]);

		die('OK');

	} // init

}

new AntimatKFF;