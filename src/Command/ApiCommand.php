<?php
namespace Civi\Cv\Command;

use Civi\Cv\BuildkitReader;
use Civi\Cv\GitRepo;
use Civi\Cv\Util\ArrayUtil;
use Civi\Cv\Util\Filesystem;
use Civi\Cv\Util\Process as ProcessUtil;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ApiCommand extends BaseCommand {

  /**
   * @var array
   */
  var $defaults;

  /**
   * @param string|null $name
   */
  public function __construct($name = NULL) {
    $this->defaults = array('version' => 3);
    parent::__construct($name);
  }

  protected function configure() {
    $this
      ->setName('api')
      ->setDescription('Call an API')
      ->addOption('in', NULL, InputOption::VALUE_REQUIRED, NULL, 'args')
      ->addOption('out', NULL, InputOption::VALUE_REQUIRED, NULL, 'pretty')
      ->addOption('json', 'j', InputOption::VALUE_NONE, 'Set input and output to JSON mode')
      ->addArgument('Entity.action', InputArgument::REQUIRED)
      ->addArgument('key=value', InputArgument::IS_ARRAY);
  }

  protected function initialize(InputInterface $input, OutputInterface $output) {
    parent::initialize($input, $output); // TODO: Change the autogenerated stub
    if ($input->getOption('json')) {
      $input->setOption('in', 'json');
      $input->setOption('out', 'json');
    }
  }


  protected function execute(InputInterface $input, OutputInterface $output) {
    \Civi\Cv\Bootstrap::singleton()->boot();
    \CRM_Core_Config::singleton();
    \CRM_Utils_System::loadBootStrap(array(), FALSE);

    list($entity, $action) = explode('.', $input->getArgument('Entity.action'));
    $params = $this->parseParams($input);

    // Drush does the following...?
    //    global $user;
    //    CRM_Core_BAO_UFMatch::synchronize($user, FALSE, 'Drupal',
    //      civicrm_get_ctype('Individual')
    //    );

    $result = \civicrm_api($entity, $action, $params);
    $this->sendResult($input, $output, $result);
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param $matches
   * @return array
   */
  protected function parseParams(InputInterface $input) {
    $args = $input->getArgument('key=value');
    switch ($input->getOption('in')) {
      case 'args':
        $params = $this->defaults;
        foreach ($args as $arg) {
          preg_match('/^([^=]+)=(.*)$/', $arg, $matches);
          $params[$matches[1]] = $matches[2];
        }
        break;

      case 'json':
        $json = stream_get_contents(STDIN);
        if (empty($json)) {
          $params = $this->defaults;
        }
        else {
          $params = array_merge($this->defaults, json_decode($json, TRUE));
        }
        break;

      default:
        throw new \RuntimeException('Unknown input format');
    }
    return $params;
  }

  /**
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param $result
   */
  protected function sendResult(InputInterface $input, OutputInterface $output, $result) {
    $outMode = $input->getOption('out');
    switch ($outMode) {
      case 'pretty':
        $output->write(print_r($result, 1));
        break;

      case 'json':
        $output->write(json_encode($result));
        break;

      default:
        throw new \RuntimeException('Unknown output format');
    }
  }

}
