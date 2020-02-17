<?php
require_once __DIR__."/../UniConstruct.trait.php";
require_once __DIR__."/../Helper.class.php";

class Draws extends Helper implements DrawsInt
{
	use UniConstruct;

	const
		FOLDER = __DIR__.'/../Game_B',
		BASE = self::FOLDER . '/base.json';

	protected
		$draws;

	private
		$drawsOwner,
		$addSelf,
		$toAllParticipants;

	/**
	 * @param cmd - 'cmdName_opt1_opt2_...etc'
	 */
	public function __construct(UniKffBot &$UKB, ?string $cmd=null)
	{
		$this->setConstruct($UKB, $cmd)->init()->routerCmd()->saveCurData();

	} //* __construct


	private function init()
	{
		// $this->log->add('self::BTNS=',null,[self::BTNS]);

		$this->getCurData();

		$this->drawsOwner = isset($this->data['current draws']['owner'])
		&& $this->chat_id === $this->data['current draws']['owner']['id'];

		$this->data['change'] = 0;

		return $this;
	} //* init


	protected function routerCmd($cmd=null)
	{
		$o = parent::routerCmd($cmd);

		$draws = &$this->data['current draws'];
		// $pumps = &$this->data['pumps'];

		if(!$o) switch ($cmd ?? $this->cmd[0]) {
			//*** Новый розыгрыш ***
			case 'new draw':
				if(!empty($draws))
				{
					$o = $this->showMainMenu([
						'text' => 'Вы не можете создать розыгрыш, пока не разыгран предыдущий. Но вы можете участвовать в существующем!'
					]);
				break;
				}

				$o = [
					'text' => 'Введите количество призов в розыгрыше.',
					'reply_markup' => [
						"inline_keyboard" => [
							[
								['text' => 1, 'callback_data' => '/Draws/prizes_count__1'],
								['text' => 2, 'callback_data' => '/Draws/prizes_count__2'],
								['text' => 3, 'callback_data' => '/Draws/prizes_count__3'],
							],
				],],];
				break;

			case 'prizes_count':

				if( !$this->drawsOwner )
				{
					$this->showMainMenu([
						'text'=> 'Менять количество призов может только спонсор розыгрыша!',
					]);
					break;
				}

				$this->data['change']++;
				$draws = [
					'owner' => $this->cbn['from'],
					'prizes_count' => $this->cmd[1][0],
				];

				$o = [
					'text' => 'Розыгрыш создан.',
					'reply_markup' => [
						"keyboard" => [
							[
								['text' => self::BTNS['general']],
							],
				],],];
				$this->addSelf = 1;

				// $this->sendToAll("Создан розыгрыш от <b>{$this->cbn['from']['first_name']}</b>. Спешите принять участие!");
				break;

			case 'show participants':
				$o = $this->showParticipants();
				$o['text'] .= "\n<a href='{$this->urlDIR}/assets/Zorro_300.png' title='ZorroClan'>&#8205;</a>";
				break;

			//* Розыгрыш
			case 'play draw':
				if(!count($draws['participants']))
				{
					$o = $this->showMainMenu([
						'text' => "Кого разыгрывать собираемся, товагисч?\n\nРегистрируйте новый розыгрыш!",
					]);
					break;
				}

				shuffle($draws['participants']);
				$winners = []; $winStr = '';

				for($i=0; $i < $draws['prizes_count']; $i++)
				{
					if(empty($draws['participants'][$i]))
						break;

					$winners[] = $draws['participants'][$i];
					$winStr .= $this->showUsername($winners[$i]);
				}

				$o = [
					'text' => "<u>Победители:</u>\n\n$winStr\n<a href='{$this->urlDIR}/assets/Zorro_300.png' title='ZorroClan'>&#8205;</a>",
					'reply_markup' => [
						"keyboard" => [
							[
								['text' => self::BTNS['general']],
							],
				],],];

				$o = array_merge_recursive($this->showParticipants(), $o);

				$this->toAllParticipants = 1;
				break;

			//* Участвовать
			case 'participate':
				$count = count($draws['participants']);

				if(in_array($this->cbn['from'], $draws['participants']))
				{
					$o = $this->showMainMenu([
						'text' => "Ты уже в регистрации на розыгрыш. Кончай сервер мучать, а то - забаню нах!\n\nА вообще уже $count чел. в розыгрыше."
					]);
					break;
				}
				elseif(
					!empty($owner = $draws['owner'])
				) {
					$this->data['change']++;
					$draws['participants'][]= $this->cbn['from'];
					$count++;

					$o = [
						'text' => "Участник " . $this->showUsername($this->cbn['from']) . " зарегистрировался в розыгрыше от {$owner['first_name']}.\nНа данный момент зарегистрировано {$count} чел."
					];
					$this->sendToOwner = 1;
				}
				else $o = $this->showMainMenu([
					'text' => 'Для вас нет доступных розыгрышей в настоящий момент.',
				]);
				break;

			default:
				$draw= [
					'text' => isset($draws['owner'])
					? "Создан розыгрыш от {$draws['owner']['first_name']}. Спешите принять участие!"
					: "На данный момент созданных розыгрышей нет. Вы можете создать свой."
				];

				$o = $this->showMainMenu($draw);
				break;
		} //*switch


		//* Подготовка и отправка
		if($o)
		{
			//* Кнопки для организатора
			if(
				$this->drawsOwner
				&& empty($o['reply_markup']['inline_keyboard'])
			)
			{
				$o['reply_markup']['keyboard'] = array_merge_recursive($o['reply_markup']['keyboard'] ?? [], [[
					['text' => self::DRS_BTNS['drs/play draw']],
					['text' => self::DRS_BTNS['drs/show participants']],
				]]);
				$this->log->add(__METHOD__.' reply_markup=',null, [$o['reply_markup'],]);
			}

			//* add keyboard options
			if(!empty($o['reply_markup']['keyboard']))
			{
				$o['reply_markup'] += ["one_time_keyboard" => false, "resize_keyboard" => true, "selective" => true];
			}

			//* Склеиваем текст перед отправкой
			if(is_array($o['text']))
			{
				$o['text'] = implode("\n\n", $o['text']);
			}

			//* Send
			if(!empty($this->toAllParticipants))
			{
				$this->toAllParticipants = null;
				foreach($draws['participants'] as $p)
				{
					$o['chat_id'] = $p['id'];
					$this->apiRequest($o);
				}
				//*
				unset($draws, $this->data['current draws']);
				$this->data['change']++;
			}
			else $this->apiRequest($o);

			//* Добавляем себя в розыгрыш при создании
			if(!empty($this->addSelf))
			{
				$this->addSelf = null;
				return $this->routerCmd('participate');
			}

			//* Отправляем владельцу розыгрыша
			if(!empty($this->sendToOwner) && !$this->drawsOwner)
			{
				$this->sendToOwner = null;
				$o['chat_id'] = $draws['owner']['id'];
				$this->apiRequest($o);
			}
		}

		return $this;
	} //* routerCmd 💡


	private function showParticipants()
	:array
	{
		$draws = &$this->data['current draws'];
		$ps = '';
		foreach($draws['participants'] as $p)
		{
			$ps .= $this->showUsername($p);
		}
		return [
			'text' => "<u>Зарегистрировались:</u> " . count($draws['participants']) . " чел.\n\n$ps\n<a href='{$this->urlDIR}/assets/Zorro_300.png' title='ZorroClan'>&#8205;</a>"
		];
	}

} //* Draws
