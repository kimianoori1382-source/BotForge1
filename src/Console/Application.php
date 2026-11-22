<?php

declare(strict_types=1);

namespace Botforge\Console;

class Application  // کلاس مدیریت اجرای برنامه
{
  public function run(array $arguments): int //$argv[0]=file path
  //$argv[1]:first user arguement
  {
    $command = 'help'; //default command

    if (isset($arguments[1])) {
      $command = $arguments[1];
    }

    if ($command === 'help' || $command === '--help') {
      $this->showHelp();
      return 0;
    }

    if ($command === 'list') {
      $this->showCommands();
      return 0;
    }

    if ($command === '--version') {
      $this->showVersion();
      return 0;
    }

    if ($command === 'make:bot') {
      $this->makeBot($arguments);
      return 0;
    }

    //STDERR= show error message  //STDOUT= show default output
    fwrite(STDERR, "Unknown command: " . $command . PHP_EOL);

    return 1;
  }

  private function showHelp(): void
  {
    echo "BotForge CLI" . PHP_EOL;
    echo PHP_EOL;
    echo "Usage: php bin/botforge [command]" . PHP_EOL;
    echo PHP_EOL;
    echo "Available commands:" . PHP_EOL;
    echo "  list       List available commands" . PHP_EOL;
    echo "  help       Show help information" . PHP_EOL;
    echo "  make:bot    Create a new bot project" . PHP_EOL;
  }

  private function showCommands(): void
  {
    echo "Available commands:" . PHP_EOL;
    echo "  list" . PHP_EOL;
    echo "  help" . PHP_EOL;
    echo "  make:bot    Create a new bot project" . PHP_EOL;
  }

  private function showVersion(): void
  {
    $composerPath = dirname(__DIR__, 2) . '/composer.json';

    $composerContent = file_get_contents($composerPath);  //reads file content as string

    $composer = json_decode($composerContent, true);

    if (isset($composer['version'])) {
      echo "BotForge version " . $composer['version'] . PHP_EOL;
      return;
    }
    echo "BotForge version unknown" . PHP_EOL;
  }

  private function makeBot(array $arguments): void
  {
    if (!isset($arguments[2])) {
      fwrite(STDERR, "Bot name is required." . PHP_EOL);
      return;
    }

    $name = $arguments[2];
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_-]*$/', $name)) {
      fwrite(STDERR, "Invalid bot name." . PHP_EOL);
      return;
    }

    if (is_dir($name)) {
      fwrite(STDERR, "Directory already exists." . PHP_EOL);
      return;
    }

    mkdir($name);

    $files = [
      'bot.php' => $this->getBotTemplate(), //کد ولیه فایل رو به صورت رشته منی تولید میکند
      '.env' => "BOT_TOKEN=\nBOT_WEBHOOK_SECRET=\nBOT_DEBUG=false\nBOT_LANGUAGE=fa\n",
      'composer.json' => "{\n    \"name\": \"botforge/" . strtolower($name) . "\",\n    \"type\": \"project\",\n    \"require\": {\n        \"php\": \"^8.2\",\n        \"botforge/telegram-bot-framework\": \"^1.0\"\n    }\n}\n",
    ];

    foreach ($files as $file => $content) {
      file_put_contents($name . '/' . $file, $content);
    }

    echo "Bot project created successfully: " . $name . PHP_EOL;
  }

  private function getBotTemplate(): string
  {
    return <<<'PHP'
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Botforge\Api\HttpClient;
use Botforge\Api\TelegramApiClient;
use Botforge\Bot;
use Botforge\Config;
use Botforge\Storage\FileStorage;
use GuzzleHttp\Client;

$config = Config::fromEnvironment();

$guzzle = new Client();

$http = new HttpClient($guzzle);

$api = new TelegramApiClient(
    $http,
    $config->getToken()
);

$storage = new FileStorage(__DIR__ . '/storage');

$bot = new Bot(
    $config,
    $api,
    $storage
);

$bot->command('start', function ($context) {
    $context->reply('Welcome to my bot!');
});

$bot->run();
PHP;
  }
}
