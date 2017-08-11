<?php

namespace Lookfeel\Repository\Commands;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class CreateRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {repository} {--model=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a repository';

    /**
     * @var
     */
    protected $repository;

    /**
     * @var
     */
    protected $model;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $filesystem
     * @param Composer   $composer
     */
    public function __construct(Filesystem $filesystem, Composer $composer)
    {
        parent::__construct();

        $this->files = $filesystem;
        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //获取repository和model两个参数值
        $argument = $this->argument('repository');
        $option = $this->option('model');
        //自动生成Repository文件
        $this->writeRepository($argument, $option);
        //重新生成autoload.php文件
        $this->composer->dumpAutoloads();
    }

    private function writeRepository($repository, $model)
    {
        if ($this->createRepository($repository, $model)) {
            //若生成成功,则输出信息
            $this->info('Success to make a ' . ucfirst($repository) . ' Repository');
        }
    }

    private function createRepository($repository, $model)
    {
        // getter/setter 赋予成员变量值
        $this->setRepository($repository);
        $this->setModel($model);
        // 创建文件存放路径
        $this->createDirectory();
        // 生成两个文件
        return $this->createClass();
    }

    private function createDirectory()
    {
        $directory = $this->getDirectory();
        //检查路径是否存在,不存在创建一个,并赋予775权限
        if (!$this->files->isDirectory($directory)) {
            return $this->files->makeDirectory($directory, 0755, true);
        }
    }

    private function getDirectory()
    {
        return Config::get('repository.directory_path');
//        return Config::get('repository.directory_eloquent_path');
    }

    private function createClass()
    {
        //渲染模板文件,替换模板文件中变量值
        $templates = $this->templateStub();
        $class = null;
        foreach ($templates as $key => $template) {
            //根据不同路径,渲染对应的模板文件
            $class = $this->files->put($this->getPath($key), $template);
        }
        return $class;
    }

    private function getPath($class)
    {
        // 两个模板文件,对应的两个路径
        $path = null;
        switch ($class) {
            case 'Eloquent':
                $path = $this->getDirectory() . DIRECTORY_SEPARATOR . $this->getRepositoryName() . '.php';
                break;
        }
        return $path;
    }

    private function getRepositoryName()
    {
        // 根据输入的repository变量参数,是否需要加上'Repository'
        $repositoryName = $this->getRepository();
        if ((strlen($repositoryName) < strlen('Repository')) || strrpos($repositoryName, 'Repository', -11)) {
            $repositoryName .= 'Repository';
        }
        return $repositoryName;
    }


    /**
     * @return mixed
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * @param mixed $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * @return mixed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    private function templateStub()
    {
        // 获取两个模板文件
        $stubs = $this->getStub();
        // 获取需要替换的模板文件中变量
        $templateData = $this->getTemplateData();
        $renderStubs = [];
        foreach ($stubs as $key => $stub) {
            // 进行模板渲染
            $renderStubs[$key] = $this->getRenderStub($templateData, $stub);
        }

        return $renderStubs;
    }

    private function getStub()
    {
        $stubs = [
            'Eloquent' => $this->files->get(__DIR__ . '/../resources/stubs/Repository' . DIRECTORY_SEPARATOR . 'repository.stub'),
        ];

        return $stubs;
    }

    private function getTemplateData()
    {
        $repositoryNamespace = Config::get('repository.repository_namespace');
        $modelNamespace = Config::get('repository.model_namespace') . '\\' . $this->getModelName();
        $className = $this->getRepositoryName();
        $modelName = $this->getModelName();

        $templateVar = [
            'repository_namespace' => $repositoryNamespace,
            'model_namespace' => $modelNamespace,
            'class_name' => $className,
            'model_name' => $modelName,
            'model_var_name' => Str::camel($modelName),
        ];

        return $templateVar;
    }

    private function getRenderStub($templateData, $stub)
    {
        foreach ($templateData as $search => $replace) {
            $stub = str_replace('$' . $search, $replace, $stub);
        }

        return $stub;
    }

    private function getModelName()
    {
        $modelName = $this->getModel();
        if (isset($modelName) && !empty($modelName)) {
            $modelName = ucfirst($modelName);
        } else {
            // 若option选项没写,则根据repository来生成Model Name
            $modelName = $this->getModelFromRepository();
        }

        return $modelName;
    }

    private function getModelFromRepository()
    {
        $repository = Str::camel($this->getRepository());
        $repository = str_replace('Repository', '', $repository);
        return ucfirst($repository);
    }

}
