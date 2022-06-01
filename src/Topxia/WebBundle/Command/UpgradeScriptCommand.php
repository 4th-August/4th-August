<?php
namespace Topxia\WebBundle\Command;

use Topxia\Service\Util\PluginUtil;
use Topxia\Service\User\CurrentUser;
use Topxia\Service\Common\ServiceKernel;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeScriptCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('topxia:upgrade-script')
             ->addArgument('version', InputArgument::REQUIRED, '要升级的版本号');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initServiceKernel();

        $code    = 'MAIN';
        $version = $input->getArgument('version');

        $this->executeScript($code, $version);
        $output->writeln("<info>执行脚本</info>");

        $this->removeCache();
        $output->writeln("<info>删除缓存</info>");

        $this->updateApp($code, $version);
        $output->writeln("<info>元数据更新</info>");
    }

    protected function executeScript($code, $version)
    {
        $scriptFile = $this->getServiceKernel()->getParameter('kernel.root_dir')."/../scripts/upgrade-{$version}.php";

        if (!file_exists($scriptFile)) {
            return;
        }

        include_once $scriptFile;
        $upgrade = new \EduSohoUpgrade($this->getServiceKernel());

        if (method_exists($upgrade, 'update')) {
            $info = $upgrade->update();
        }
    }

    protected function removeCache()
    {
        $cachePath  = $this->getServiceKernel()->getParameter('kernel.root_dir').'/cache/'.$this->getServiceKernel()->getEnvironment();
        $filesystem = new Filesystem();
        $filesystem->remove($cachePath);

        if (empty($errors)) {
            PluginUtil::refresh();
        }
    }

    protected function updateApp($code, $version)
    {
        $app = $this->getAppService()->getAppByCode($code);

        $newApp = array(
            'code'        => $code,
            'version'     => $version,
            'fromVersion' => $app['version'],
            'updatedTime' => time()
        );

        $this->getLogService()->info('system', 'update_app_version', "命令行更新应用「{$app['name']}」版本为「{$version}」");
        return $this->getAppDao()->updateApp($app['id'], $newApp);
    }

    private function initServiceKernel()
    {
        $serviceKernel = ServiceKernel::create('prod', false);
        $serviceKernel->setParameterBag($this->getContainer()->getParameterBag());
        $serviceKernel->setConnection($this->getContainer()->get('database_connection'));
        $currentUser = new CurrentUser();
        $currentUser->fromArray(array(
            'id'        => 0,
            'nickname'  => '游客',
            'currentIp' => '127.0.0.1',
            'roles'     => array()
        ));
        $serviceKernel->setCurrentUser($currentUser);
    }

    protected function getAppDao()
    {
        return $this->getServiceKernel()->createDao('CloudPlatform.CloudAppDao');
    }

    protected function getAppService()
    {
        return $this->getServiceKernel()->createService('CloudPlatform.AppService');
    }

    protected function getLogService()
    {
        return $this->getServiceKernel()->createService('System.LogService');
    }
}
