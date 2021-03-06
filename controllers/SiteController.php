<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\data\ActiveDataProvider;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {   
        $searchModel = new \app\models\Produto;
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        Yii::$app->view->registerJsFile('@web/js/site/index.js', ['depends' => [\app\assets\AppAsset::className()]]);

        return $this->render('index', ['provider'=>$dataProvider, 'model'=>$dataProvider->models]);
    }

    /**
     * Recupera e trata dados do formulário de venda
     * 
     * */
    public function actionVenda() 
    {
        if(!Yii::$app->request->isAjax)
            throw new BadRequestHttpException("Formato de requisição inválido!");

        $ids = Yii::$app->request->post('ids');


        $produtos = \app\models\Produto::find()->where(['id' => $ids])->all();
        $total = 0;

        $model = new \app\models\Venda;
        foreach ($produtos as $p) {
            $total += $p->price;
        }
        $model->ven_funcionario_id =  1;
        $model->valor_total =  $total;

        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if($model->save()){
            $model->refresh();
            foreach ($produtos as $p) {
                $model->link('produtos',  $p);
            }
            if($model->save()) {
                return [
                    'message' => 'success',
                    'code' => '200'
                ];
            }
        }
        return [
            'message' => $model->getErrors(),
            'code' => '400'
        ];
    }

    public function actionCadastrarProduto()
    {

        $productForm = new \app\models\Produto;

        if($post = Yii::$app->request->post()){
            if($productForm->load($post) && $productForm->validate()){
                $productForm->save();
                Yii::$app->session->setFlash('success', 'Produto salvo com sucesso!');

                //Unset model attributes
                $productForm = new \app\models\Produto;
            }else 
            {
                Yii::$app->session->setFlash('error', 'Erro ao salvar produto: '.$productForm->getErrors());
                print_r($productForm->getErrors());die();
            }
        }

        return $this->render('cadastrar-produto', ['productForm' => $productForm]);
    }

    public function actionProdutos(){

        $query = \app\models\Produto::find();
        $provider = new ActiveDataProvider([
            'query'=>$query,
            'pagination'=>[
                'pageSize'=>20
            ]
        ]);        
        return $this->render('produtos', ['models'=>$provider->models, 'provider'=>$provider]);
    }

    public function actionCadastrarFuncionario()
    {

        $funcForm = new \app\models\Funcionario;

        if($post = Yii::$app->request->post()){
            if($funcForm->load($post) && $funcForm->validate()){
                $funcForm->save();
                Yii::$app->session->setFlash('success', 'Funcionario cadastrado com sucesso!');

                //Unset model attributes
                $funcForm = new \app\models\Funcionario;
            }else 
            {
                Yii::$app->session->setFlash('error', 'Erro ao salvar funcionario: '.$funcForm->getErrors());
                print_r($funcForm->getErrors());die();
            }
        }

        return $this->render('cadastrar-funcionario', ['funcForm' => $funcForm]);
    }

    public function actionFuncionarios(){

        $query = \app\models\Funcionario::find();
        $provider = new ActiveDataProvider([
            'query'=>$query,
            'pagination'=>[
                'pageSize'=>20
            ]
        ]);        
        return $this->render('funcionarios', ['models'=>$provider->models, 'provider'=>$provider]);
    }
}
