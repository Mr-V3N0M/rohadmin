<?php
 require_once(Yii::app()->basePath . '/vendor/mike42/escpos-php/autoload.php');
// require_once(Yii::app()->basePath . '/vendor/flot/Chart.php');
// require_once(Yii::app()->basePath . '/vendor/flot-master/Plugin.php');

use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class ReportController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions

			// 	'actions'=>array('index','view'),
			// 	'users'=>array('*'),
			// ),
			// array('allow', // allow authenticated user to perform 'create' and 'update' actions
			// 	'actions'=>array('create','update'),
			// 	'users'=>array('@'),
			// ),
			// array('allow', // allow admin user to perform 'admin' and 'delete' actions
			// 	'actions'=>array('admin','delete'),
			// 	'users'=>array('admin'),
			// ),
			// array('allow', // allow admin user to perform 'admin' and 'delete' actions
			// 	'actions'=>array('print','print'),
			// 	'users'=>array('@'),
			// ),
			// array('deny',  // deny all users
			// 	'users'=>array('*'),
			// ),
				'actions'=>array('admin','delete','view','index'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('create','update'),
				'users'=>array('@'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('admin','delete','view','index'),
				'users'=>array('admin'),
			),
			array('allow', // allow admin user to perform 'admin' and 'delete' actions
				'actions'=>array('print','print'),
				'users'=>array('@'),
			),
			// array('deny',  // deny all users
			// 	'users'=>array('*'),
			// ),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$model=new Cart;
		$cat="";
		$chartdata="";
		$catstatus="";
		$chartstatus=false;
		$chartweekdata=array();
		$weekdays=array();
		$chartname="";
		$av1=array();

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
//echo date( "Y-m-d", strtotime( $_GET['Cart']['status']." -7 days " ) );
		if(isset($_GET['Cart']))
		{
			$chartname=$_GET['Cart']['time'];
			$catstatus=$_GET['Cart']['status'];
			$data = Yii::app()->db->createCommand()
    			->select('cart.id, cart.time,DATE_FORMAT(cart.time, "%Y-%m-%d") as date,history.productId,history.productName,sum(history.number) as hinumber')
   				->from('cart')
   				   ->join('history', 'cart.cartId=history.cartId');
   				if($_GET['Cart']['time']=='day'){
				   $data=$data->where('DATE_FORMAT(cart.time, "%Y-%m-%d")=:date', array(':date'=>$_GET['Cart']['status']));
				$data=$data->group('productId');
   				}
   				if($_GET['Cart']['time']=='week' && $_GET['Cart']['status']!=""){
				   $data=$data->where("cart.time BETWEEN  '".$_GET['Cart']['status']." 00:00:00' AND '".date( "Y-m-d", strtotime( $_GET['Cart']['status']." +6 days " ) )." 23:59:00'");
//				   ->where('DATE_FORMAT(cart.time, "%Y-%m-%d")>=:date', array(':date'=>date( "Y-m-d", strtotime( $_GET['Cart']['status']." +7 days " ) )));
				$data=$data->group('productId,date');
   				}
				$data=$data->order('date');
				$data=$data->queryAll();

			 $chartstatus=true;
			 $aprocount=array();
			 if($_GET['Cart']['status']!=""){

			 $chartstatus=false;
			 foreach ($data as $key => $value) {
//			 	if($aprocount)
			 	// day
			 	$cat=$cat."'".$value['productName']."',"; //($value['date']);
			 	$chartdata=$chartdata.$value['hinumber'].","; //($value['date']);

			 	// week

			 	if(!in_array($value['date'], $weekdays, true)){
			   //      array_push($liste, $value);
				 	 array_push($weekdays,$value['date']);
				 }
			 	if(!in_array($value['productName'], $aprocount, true)){
				 	array_push($aprocount,$value['productName']);
				 }
			 }
			
$aci=0;
				for($i=0;$i<count($weekdays);$i++){
					$av=array();
					$av['name']=$weekdays[$i];
					$cv=array();
				
				for($j=0;$j<count($aprocount);$j++){
//					echo $aprocount[$j].' - J ';
//					$cv[]=0;

					$data = Yii::app()->db->createCommand()
    			->select('DATE_FORMAT(cart.time, "%Y-%m-%d") as date,sum(history.number) as hinumber')
   				->from('cart')
   				->join('history', 'cart.cartId=history.cartId')
   				->where("DATE_FORMAT(cart.time, '%Y-%m-%d') ='".$weekdays[$i]."' AND productName='".$aprocount[$j]."'")
   				->group('productId,date')
   				->order('date')
   				->queryAll();
   				if(empty($data)){
								array_push($cv,0);
   				}else{
								array_push($cv,(int)$data[0]['hinumber']);
   				}
				}
					$av['data']=$cv;
					// print_r($cv);
					// echo ": ".$av['name'];
					// echo "<br/>";
					// echo "<br/>";

			 	if(!in_array($av, $av1, true)){
					 array_push($av1,$av);
				 }
				}

			}
		}

		$this->render('admin',array(
			'model'=>$model,
			'cat'=>$aprocount,
			'chartname'=>$chartname,
			'chartdata'=>$chartdata,
			'catstatus'=>$catstatus,
			'chartstatus' =>$chartstatus,
			'chartweekdata'=>$av1,
		));
	}
	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);

		if(isset($_POST['Cart']))
		{
			$model->attributes=$_POST['Cart'];
			if($model->save())
				$this->redirect(array('view','id'=>$model->id));
		}

		$this->render('update',array(
			'model'=>$model,
		));
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('Cart');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{

		$model=new Cart;
		$cat=array();
		$chartdata="";
		$catstatus="";
		$av1="";
		$chartname="";
	 	$chartstatus=false;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		if(isset($_GET['Cart']))
		{
//			$model->attributes=$cat;
			// if($model->save())
			// 	$this->redirect(array('view','id'=>$model->id));
		}
		$this->render('admin',array(
			'model'=>$model,
			'cat'=>$cat,
			'chartdata'=>$chartdata,
			'chartname'=>$chartname,
			'catstatus'=>$catstatus,
			'chartstatus' =>$chartstatus,
			'chartweekdata'=>$av1,

		));
// //		$model=new History;
// 		$model=new Cart('search');
// 		$model->unsetAttributes();  // clear any default values
// 		if(isset($_GET['Cart']))
// 			$model->attributes=$_GET['Cart'];
// 			$model->dbCriteria->order='time DESC';
// 		$this->render('admin',array(
// 			'model'=>$model,
// 		));
	}
	public function actionPrint()
	{
		$this->render('print');

	}
	public function actionDynamicStates()
	{
			    $vals['value']="";
		echo CHtml::tag('option', $vals , CHtml::encode('Select'),true);
		if($_POST['Cart']['time']!=""){
        $data = Yii::app()->db->createCommand()
    			->select('id,DATE_FORMAT(time, "%Y-%m-%d") as date')
   				->from('cart')
				->group('date')
			    ->queryAll();

//		$state = isset($_POST['hidden_state']);
		
		foreach($data as $value) {
			print_r($value['date']);
//			echo $value.' :'.$name;
//			echo "<br/>";
//			echo CHtml::tag('option', $opt , CHtml::encode('10/04/2018'),true);
			$opt = array();
			$opt['value'] = $value['date'];
			// if($state == $value) $opt['selected'] = "selected";
			echo CHtml::tag('option', $opt , CHtml::encode($value['date']),true);
		}}
		die;
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Cart the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Cart::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Cart $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='cart-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
}
