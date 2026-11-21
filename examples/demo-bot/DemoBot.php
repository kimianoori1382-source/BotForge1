<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Botforge\Api\HttpClient;
use Botforge\Api\TelegramApiClient;
use Botforge\Bot;
use Botforge\Config;
use Botforge\Storage\FileStorage;
use Botforge\Keyboard\Keyboard;
use Botforge\Conversation\ConversationManager;
use GuzzleHttp\Client;

$root = dirname(__DIR__, 2);

$config = Config::fromEnvironment();

$http = new HttpClient(new Client([
  'proxy' => 'http://127.0.0.1:10808',
]));

$api = new TelegramApiClient(
  $http,
  $config->getToken()
);

$storage = new FileStorage($root . '/storage');

$bot = new Bot(
  $config,
  $api,
  $storage
);

$conversation = new ConversationManager($storage);

$adminId = (int) getenv('BOT_ADMIN_ID');

$products = [
  ['id' => 1, 'title' => 'PHP Course'],
  ['id' => 2, 'title' => 'Laravel Course'],
  ['id' => 3, 'title' => 'Python Course'],
  ['id' => 4, 'title' => 'JavaScript Course'],
  ['id' => 5, 'title' => 'Docker Course'],
  ['id' => 6, 'title' => 'Linux Course'],
];

$messages = [
  'en' => [
    'welcome' => 'Welcome to BotForge!',
    'language' => 'Language changed to English.',
    'choose_language' => 'Choose your language:',
    'products' => 'Product List:',
    'register' => 'Please enter your name.',
    'help' => 'Available commands: /start, /products, /register, /confirm, /cancel, /lang, /admin, /limited',
    'name' => 'Please enter your name:',
    'invalid_name' => 'Name must contain at least 2 characters.',
    'phone' => 'Please enter your phone number:',
    'invalid_phone' => 'Invalid phone number. Enter 10 to 15 digits.',
    'confirm' => 'Confirm your registration:',
    'completed' => 'Registration completed successfully.',
    'cancelled' => 'Registration cancelled.',
  ],
  'fa' => [
    'welcome' => 'به بات BotForge خوش آمدید!',
    'language' => 'زبان به فارسی تغییر کرد.',
    'choose_language' => 'زبان موردنظر خود را انتخاب کنید:',
    'products' => 'فهرست محصولات:',
    'register' => 'لطفاً نام خود را وارد کنید.',
    'help' => 'دستورات: /start, /products, /register, /confirm, /cancel, /lang, /admin, /limited',
    'name' => 'لطفاً نام خود را وارد کنید:',
    'invalid_name' => 'نام باید حداقل ۲ حرف داشته باشد.',
    'phone' => 'لطفاً شماره تلفن خود را وارد کنید:',
    'invalid_phone' => 'شماره تلفن معتبر نیست. شماره‌ای با ۱۰ تا ۱۵ رقم وارد کنید.',
    'confirm' => 'اطلاعات ثبت‌نام خود را تأیید کنید:',
    'completed' => 'ثبت‌نام با موفقیت انجام شد.',
    'cancelled' => 'ثبت‌نام لغو شد.',
  ],
];

$languageKey = 'user_language:';

$mainKeyboard = (new Keyboard())
  ->row([
    ['text' => 'Products', 'callback_data' => 'menu:products'],
    ['text' => 'Register', 'callback_data' => 'menu:register'],
  ])
  ->row([
    ['text' => 'Language', 'callback_data' => 'menu:language'],
    ['text' => 'Help', 'callback_data' => 'menu:help'],
  ])
  ->inline();

$languageKeyboard = (new Keyboard())
  ->row([
    ['text' => 'English', 'callback_data' => 'lang:en'],
    ['text' => 'فارسی', 'callback_data' => 'lang:fa'],
  ])
  ->inline();

$getLanguage = function ($ctx) use ($storage, $languageKey, $messages): string {
  $language = $storage->get($languageKey . $ctx->getUser()->id);

  if (!isset($messages[$language])) {
    $language = 'en';
  }

  return $language;
};

$showProducts = function ($ctx, int $page = 1) use ($products, $messages, $getLanguage): void {
  $language = $getLanguage($ctx);
  $keyboard = Keyboard::paginate($products, $page, 3, 'products');

  $ctx->sendKeyboard($messages[$language]['products'], $keyboard);
};

$bot->command('start', function ($ctx) use ($storage, $messages, $languageKey, $mainKeyboard): void {
  $userId = $ctx->getUser()->id;

  $language = $storage->get($languageKey . $userId);

  if (!isset($messages[$language])) {
    $language = 'en';
    $storage->set($languageKey . $userId, $language);
  }

  $parts = preg_split('/\s+/', trim($ctx->getMessage()->text));

  $message = $messages[$language]['welcome'];

  if (isset($parts[1])) {
    $message .= "\nPayload: " . $parts[1];
  }

  $ctx->sendKeyboard($message, $mainKeyboard);
});

$bot->command('products', function ($ctx) use ($showProducts): void {
  $showProducts($ctx);
});

$bot->command('lang', function ($ctx) use ($storage, $messages, $languageKey): void {
  $userId = $ctx->getUser()->id;
  $parts = preg_split('/\s+/', trim($ctx->getMessage()->text));

  if (isset($parts[1]) && isset($messages[$parts[1]])) {
    $storage->set($languageKey . $userId, $parts[1]);
    $ctx->reply($messages[$parts[1]]['language']);
    return;
  }

  $ctx->reply('Use /lang en or /lang fa');
});

$bot->command('register', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  $conversation->start($userId, 'registration');

  $ctx->reply($messages[$language]['name']);
});

$bot->command('cancel', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  if (!$conversation->isActive($userId)) {
    $ctx->reply('No active registration.');
    return;
  }

  $conversation->cancel($userId);
  $ctx->reply($messages[$language]['cancelled']);
});

$bot->command('confirm', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  if (
    !$conversation->isActive($userId)
    || $conversation->getStep($userId) !== 'confirm'
  ) {
    $ctx->reply('No registration to confirm.');
    return;
  }

  $name = $conversation->getData($userId, 'name');
  $phone = $conversation->getData($userId, 'phone');

  $conversation->finish($userId);

  $ctx->reply(
    $messages[$language]['completed']
      . "\nName: " . $name
      . "\nPhone: " . $phone
  );
});

$bot->command('admin', function ($ctx) use ($adminId): void {
  if ($adminId === 0 || $ctx->getUser()->id !== $adminId) {
    $ctx->reply('Access denied.');
    return;
  }

  $ctx->reply('Welcome, Admin!');
});

$bot->command('limited', function ($ctx) use ($storage): void {
  $userId = $ctx->getUser()->id;
  $key = 'rate_limit:' . $userId;

  if ($storage->has($key)) {
    $ctx->reply('Please wait before using this command again.');
    return;
  }

  $storage->set($key, true, 10);

  $ctx->reply('Command executed successfully.');
});

$bot->command('help', function ($ctx) use ($messages, $getLanguage): void {
  $language = $getLanguage($ctx);
  $ctx->reply($messages[$language]['help']);
});

$bot->callback('menu:products', function ($ctx) use ($products, $messages, $getLanguage): void {
  $callback = $ctx->getCallbackQuery();
  $language = $getLanguage($ctx);

  $keyboard = Keyboard::paginate($products, 1, 3, 'products');

  $ctx->getApi()->editMessageText([
    'chat_id' => $callback->message->chat->id,
    'message_id' => $callback->message->messageId,
    'text' => $messages[$language]['products'],
    'reply_markup' => $keyboard,
  ]);

  $ctx->answerCallback();
});

$bot->callback('menu:register', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  $conversation->start($userId, 'registration');

  $ctx->answerCallback();
  $ctx->reply($messages[$language]['name']);
});

$bot->callback('menu:language', function ($ctx) use ($messages, $getLanguage, $languageKeyboard): void {
  $language = $getLanguage($ctx);

  $ctx->answerCallback();
  $ctx->sendKeyboard($messages[$language]['choose_language'], $languageKeyboard);
});

$bot->callback('menu:help', function ($ctx) use ($messages, $getLanguage): void {
  $language = $getLanguage($ctx);

  $ctx->answerCallback();
  $ctx->reply($messages[$language]['help']);
});

$bot->callback('lang:en', function ($ctx) use ($storage, $messages, $languageKey): void {
  $storage->set($languageKey . $ctx->getUser()->id, 'en');

  $ctx->answerCallback();
  $ctx->reply($messages['en']['language']);
});

$bot->callback('lang:fa', function ($ctx) use ($storage, $messages, $languageKey): void {
  $storage->set($languageKey . $ctx->getUser()->id, 'fa');

  $ctx->answerCallback();
  $ctx->reply($messages['fa']['language']);
});

$bot->callbackRegex('/^products:page:([0-9]+)$/', function ($ctx) use ($products, $messages, $getLanguage): void {
  $callback = $ctx->getCallbackQuery();
  $language = $getLanguage($ctx);

  $page = (int) str_replace('products:page:', '', $callback->data);

  $keyboard = Keyboard::paginate($products, $page, 3, 'products');

  $ctx->getApi()->editMessageText([
    'chat_id' => $callback->message->chat->id,
    'message_id' => $callback->message->messageId,
    'text' => $messages[$language]['products'],
    'reply_markup' => $keyboard,
  ]);

  $ctx->answerCallback();
});

$bot->callbackRegex('/^products:([0-9]+)$/', function ($ctx) use ($products): void {
  $callback = $ctx->getCallbackQuery();
  $productId = (int) str_replace('products:', '', $callback->data);

  foreach ($products as $product) {
    if ($product['id'] === $productId) {
      $ctx->answerCallback($product['title']);
      return;
    }
  }

  $ctx->answerCallback('Product not found.');
});

$bot->textRegex('/^.+$/s', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $message = trim($ctx->getMessage()->text);
  $language = $getLanguage($ctx);

  if (!$conversation->isActive($userId)) {
    return;
  }

  $step = $conversation->getStep($userId);

  if ($step === 'start') {
    if (strlen($message) < 2) {
      $ctx->reply($messages[$language]['invalid_name']);
      return;
    }

    $conversation->setData($userId, 'name', $message);
    $conversation->setStep($userId, 'phone');

    $ctx->reply($messages[$language]['phone']);
    return;
  }

  if ($step === 'phone') {
    if (!preg_match('/^[0-9]{10,15}$/', $message)) {
      $ctx->reply($messages[$language]['invalid_phone']);
      return;
    }

    $conversation->setData($userId, 'phone', $message);
    $conversation->setStep($userId, 'confirm');

    $name = $conversation->getData($userId, 'name');

    $keyboard = (new Keyboard())
      ->row([
        ['text' => 'Confirm', 'callback_data' => 'registration:confirm'],
        ['text' => 'Cancel', 'callback_data' => 'registration:cancel'],
      ])
      ->inline();

    $ctx->sendKeyboard(
      $messages[$language]['confirm']
        . "\nName: " . $name
        . "\nPhone: " . $message,
      $keyboard
    );
  }
});

$bot->callback('registration:confirm', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  if (
    !$conversation->isActive($userId)
    || $conversation->getStep($userId) !== 'confirm'
  ) {
    $ctx->answerCallback();
    return;
  }

  $name = $conversation->getData($userId, 'name');
  $phone = $conversation->getData($userId, 'phone');

  $conversation->finish($userId);

  $ctx->answerCallback();
  $ctx->reply(
    $messages[$language]['completed']
      . "\nName: " . $name
      . "\nPhone: " . $phone
  );
});

$bot->callback('registration:cancel', function ($ctx) use ($conversation, $messages, $getLanguage): void {
  $userId = $ctx->getUser()->id;
  $language = $getLanguage($ctx);

  if ($conversation->isActive($userId)) {
    $conversation->cancel($userId);
  }

  $ctx->answerCallback();
  $ctx->reply($messages[$language]['cancelled']);
});

$bot->run();
