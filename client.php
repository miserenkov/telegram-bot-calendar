<?php
/**
 * Created by PhpStorm.
 * Author: Misha Serenkov
 * Email: mi.serenkov@gmail.com
 * Date: 15.10.2017 19:37
 */

require_once 'vendor/autoload.php';
require_once 'calendar.php';

$env = require_once 'env.php';

if (!file_exists('hooks_sets.bin')) {
    $bot = new \TelegramBot\Api\BotApi($env['TELEGRAM_BOT_TOKEN']);

    try {
        $bot->setWebhook('https://telegram.serenkov.tk/client.php');
        file_put_contents('hooks_sets.bin', date('Y-m-d H:i:s'));
    } catch (\Throwable $throwable) {
        file_put_contents('errors.log', sprintf("[SetWebHooks]\t[%s]\t%s\n", date('Y-m-d H:i:s'), $throwable->getMessage()), FILE_APPEND);
        return;
    }
}

try {
    /** @var \TelegramBot\Api\BotApi|\TelegramBot\Api\Client $bot */
    $bot = new \TelegramBot\Api\Client($env['TELEGRAM_BOT_TOKEN']);

    $bot->callbackQuery(function ($callback) use ($bot) {
        /** @var \TelegramBot\Api\Types\CallbackQuery $callback */

        if ($callback->getData() === 'null_callback') {
            $bot->answerCallbackQuery($callback->getId(), '');
        } else {
            $message = $callback->getMessage();

            $callbackRoute = explode('-', $callback->getData());

            if ($callbackRoute[0] === 'calendar' && $callbackRoute[1] === 'month') {
                $calendar = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(get_calendar((int)$callbackRoute[2], (int)$callbackRoute[3]));

                $bot->editMessageReplyMarkup($message->getChat()->getId(), $message->getMessageId(), $calendar);
            } elseif ($callbackRoute[0] === 'calendar' && $callbackRoute[1] === 'year') {
                $months = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(get_months_list((int)$callbackRoute[2]));

                $bot->editMessageReplyMarkup($message->getChat()->getId(), $message->getMessageId(), $months);
            } elseif($callbackRoute[0] === 'calendar' && $callbackRoute[1] === 'months_list') {
                $months = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(get_months_list((int)$callbackRoute[2]));

                $bot->editMessageReplyMarkup($message->getChat()->getId(), $message->getMessageId(), $months);
            } elseif($callbackRoute[0] === 'calendar' && $callbackRoute[1] === 'years_list') {
                $months = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(get_years_list((int)$callbackRoute[2]));

                $bot->editMessageReplyMarkup($message->getChat()->getId(), $message->getMessageId(), $months);
            } else {
                $bot->answerCallbackQuery($callback->getId(), $callback->getData());
                return;
            }

            $bot->answerCallbackQuery($callback->getId(), '');
        }
    });

    $bot->command('start', function ($message) use ($bot) {
        /** @var \TelegramBot\Api\Types\Message $message */
        $bot->sendMessage($message->getChat()->getId(), "Hey!\n/calendar - отобразить календарь");
    });

    $bot->command('calendar', function ($message) use ($bot) {
        /** @var \TelegramBot\Api\Types\Message $message */

        $calendar = new \TelegramBot\Api\Types\Inline\InlineKeyboardMarkup(get_calendar((int)date('m'), (int)date('Y')));

        $bot->sendMessage($message->getChat()->getId(), 'Calendar', null, false, null, $calendar);
    });

    $bot->run();

} catch (\Throwable $throwable) {
    file_put_contents('errors.log', sprintf("[TelegramAPI]\t[%s]\t%s\n", date('Y-m-d H:i:s'), $throwable->getMessage()), FILE_APPEND);
    return;
}