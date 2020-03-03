<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);

require_once "../CommonBot.class.php";


class AntimatKFF extends CommonBot
{
	const
		MAX_TRY = 5,
		PRETREATMENT = [
			[
				'~[a@]~i','~6~','~B~','~g~','~[eєї]~iu','~[z3]~i','~[uiі]~iu','~[o0]~i','~p~i','~y~i','~x~i',"~(.)\\1+~u",
				"~['`\"*_|/\\.,\\~\\-+#$%^&\\?\\!\\(\\)]~"
			],
			[
				'а','б','в','д','е','з','и','о','р','у','х', "$1",'',''
			]
		],

		REPLACEMENT = " <b>[<a href='https://js-master.ru/content/5.Razrabotki/Antimat_plus/'>цензура</a>]</b> ",

		PATTERNS = [
			'~х\\s*?у(?![бж])\\s*?[ийеёюя](?![з])~iu',
			//* пизда
			'~п(?![ор]).?[еёи].{0,2}?з.{0,2}?да?~iu',
			//* блядь, пидар
			'~(?:[^аеор]|\\s|^)[бм]и?ля[дт]ь?|п[еи]д[ао]?р~iu',
			'~г[ао]вно?|г[ао]ндон|жоп[аеу]|[^о]мандав?[^лрт]|\\bауе~iu',
			//* ебать
			'~(?:[^вджл-нр-тч-щ]|^|\\s)[ьъ]?[её]б\\W*?[^ы\\b]~iu',
			'~сра[лт]ь?|залупа?|дроч~iu',
			// фразы
			'~педик|гомик~iu',
			# Test
			// '~123~',
		];

	protected
		# Test mode, bool
		$__test = 1 ;



	public function __construct()
	{
		//* Set local data
		$this->botFileInfo = new kffFileInfo(__FILE__);

		/* Запускаем скрипт
		 * Protect from CommonBot
		*/
		parent::__construct()
			->checkLicense()
			->init();

	} //__construct

	/**
	 *
	 */
	private function init()
	{
		if(empty($this->inputData) || !$this->message)
		{
			$this->log->add('Нет входящего запроса');
			die ('Нет входящего запроса');
		}
		else $this->log->add('$this->message', null, [$this->message]);

		$text = $this->message['text'];
		$pretreatmentText = preg_replace(self::PRETREATMENT[0], self::PRETREATMENT[1], $text);

		$censure = preg_replace(self::PATTERNS, self::REPLACEMENT, $pretreatmentText);

		//* Мата нет
		if(!strcmp($pretreatmentText, $censure))
		{
			$this->log->add("censure FAIL", null, [$text,$pretreatmentText, $censure]);
			return;
		}
		else
		{
			$this->log->add("censure SUCCESS", null, [$text,$pretreatmentText, $censure]);
		}

		$user = $this->message['from']['username'];
		$base = \H::json('base.json');
		$base[$user] = $base[$user] ?? ['count'=>0];
		$this->log->add(__METHOD__ . '$base[$user] = ', null, [$base[$user]]);

		if(
			is_numeric($base[$user]['count'])
			&& ++$base[$user]['count'] > self::MAX_TRY
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
			|| $base[$user]['count'] <= (self::MAX_TRY + 1)
		)
		{
			file_put_contents(
				"base.json",
				json_encode($base, JSON_UNESCAPED_UNICODE|JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES), LOCK_EX
			);
			$this->responseData['text'] = $censure;
			$this->log->add("apiRequest = ", null, [$this->apiRequest($this->responseData)]);
		}

		$this->log->add("apiResponseJSON = ", null, [$this->apiResponseJSON($this->responseData, 'deleteMessage')]);

		die('OK');

	} // init

}

new AntimatKFF;