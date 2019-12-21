<?php

namespace app\controllers;

// sesion

if(@isset($_SESSION['db']))
{ 
	// echo $_SESSION['nombre'];
} 
//si no tiene sesion se redirecciona al login
else
{
	echo "<script> window.location=\"index.php?r=tbempresas%2Fcreate\";</script>";
	die;
}

use Yii;
use app\models\Tbcontratos;
use app\models\TbcontratosBuscar;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\models\Terceros;
use app\models\Tbtercerossucursal;
use app\models\Tbdetacontratosveh;
use app\models\Vehiculos;
use app\models\Tbpoblaciones;
use app\models\Pdf;
use app\models\Tbempresa;
use app\models\Tbusuarios;


/**
 * TbcontratosController implements the CRUD actions for Tbcontratos model.
 */
class TbcontratosController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }
	
	public $estado = 
	[
		"ACTIVO" => "ACTIVO", 
		"INACTIVO" => "INACTIVO"
	];
	
	
	public $tipoContrato = 
	[
		"EMPRESARIAL" => "EMPRESARIAL -> CONTRATO PARA TRANSPORTE EMPRESARIAL",
		"ESCOLAR" => "ESCOLAR -> CONTRATO PARA TRANSPORTE DE ESTUDIENTES",
		"OCASIONAL" => "OCASIONAL -> CONTRATO PARA TRANSPORTE DE UN GRUPO ESPECIFICO DE USUARIOS",
		"SALUD" => "SALUD CONTRATO -> PARA TRANSPORTE DE USUARIOS DEL SERVICIO DE SALUD ",
		"TURISTICO" => "TURISTICO -> CONTRATO PARA TRANSPORTE DE TURISTAS",
	];

    /**
     * Lists all Tbcontratos models.
     * @return mixed
     */
    public function actionIndex()
    {
		
        $searchModel = new TbcontratosBuscar();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Tbcontratos model.
     * @param integer $idContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($idContrato, $anioContrato)
    {
        return $this->render('view', [
            'model' => $this->findModel($idContrato, $anioContrato),
        ]);
    }

    /**
     * Creates a new Tbcontratos model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
		
        $model = new Tbcontratos();

		
		$departamento = Tbpoblaciones::find()->select("Departamento")->groupBy("Departamento")->all();
		$departamento = ArrayHelper::map($departamento,"Departamento","Departamento");
	
		$añoActual = date("Y");
       
        if ($model->load(Yii::$app->request->post())) 
		{
			//saber cual es numero de contrato que debe seguir
			$idContrato = Tbdetacontratosveh::find()->select('max(idContrato)')->andWhere("	anioContrato = $añoActual ")->scalar() +1; 
						
			$model->idContrato 		= $idContrato ;
			$model->anioContrato	= $añoActual  ;
			$model->nroContrato 	= $idContrato . "-" . $añoActual ;
			$model->sucursalActiva 	= 0 ;
			
			// echo "<pre>"; print_r(Yii::$app->request->post()); echo "</pre>"; 
			// die;
			@$sucursal = Yii::$app->request->post()['Tbcontratos']['sucursalActiva'];
			//se guarda desspues de completar los campos requeridos
			$model->save();
			
			//se guardan los vehiculos en la tabla intermedia
			$vehiculos = [];
			foreach (Yii::$app->request->post()['Tbdetacontratosveh'] as $key => $contratosveh)
			{
				$vehiculos[$key] = new Tbdetacontratosveh();
			}
			
	
			if (Tbdetacontratosveh::loadMultiple($vehiculos, Yii::$app->request->post())) 
			{
				foreach ($vehiculos as $key => $vehiculo) 
				{
					$vehiculo->idContrato = $model->idContrato;
					$vehiculo->save(false);
					
				}
			}
			
			$valorContratoletras = Yii::$app->request->post()['valorLetras'] ;
			

				$model = new Tbcontratos();

				$contrato = $this->actionPdf($idContrato, $añoActual, $valorContratoletras,$sucursal);

			return $this->render('create', [
				'model' => $model,
				'estado' => $this->estado,
				'tipoContrato' => $this->tipoContrato,
				'departamento' => $departamento,
				'guardado' => 1,
				'contrato' => $contrato,
			]);
            // return $this->redirect(['view', 'idContrato' => $model->idContrato, 'anioContrato' => $model->anioContrato]);
        }
        return $this->render('create', [
            'model' => $model,
			'estado' => $this->estado,
			'tipoContrato' => $this->tipoContrato,
			'departamento' => $departamento,
        ]);
    }

	
    /**
     * Updates an existing Tbcontratos model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $idContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($idContrato, $anioContrato)
    {
        $model = $this->findModel($idContrato, $anioContrato);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'idContrato' => $model->idContrato, 'anioContrato' => $model->anioContrato]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }


	public function actionSucursal($idtercero)
	{
		$sucursales = tbtercerossucursal::find()->AndWhere("idtercero = $idtercero")->all();
		$sucursales =  ArrayHelper::map($sucursales,'idterceroSucursal','nombreSucursalTer');
		
		return json_encode($sucursales);
	}
	
	
	public function actionVehiculos($form,$num)
	{
		$model = new Tbdetacontratosveh();

		if ($model->load(Yii::$app->request->post()) && $model->save()) {
			return $this->redirect(['view', 'id' => $model->placa]);
		}

		$datosVehiculos = Vehiculos::find()->all();
		$datosVehiculos = ArrayHelper::map($datosVehiculos,"placa","NroInterno");
		
		$placa = [];
	
		foreach ($datosVehiculos as $key => $d)
		{
			$placa[ $key ] = $key ." / ". $d;
		}
		
		return $this->renderPartial('../tbdetacontratosveh/create', [
			'model' => $model,
			'form'  => $form,
			'placa' => $placa,
			'num'=> $num,
		]);
	}


	public function actionTercero($idtercero)
	{
		$tercero = Terceros::find()->AndWhere("idtercero = $idtercero")->all();
		$tercero = ArrayHelper::toArray($tercero, 'idtercero');
		 
		$ciudadTercero = Tbpoblaciones::find()->AndWhere(['idCenPob'  => $tercero[0]['idCenPob'] ])->one();
		$tercero[0]['idCenPob'] = $ciudadTercero->CentroPoblado;
		return json_encode($tercero[0]);
	}


	public function actionInfoTercero($filtro)
	{
		
		$tercero = Terceros::find()
		->andWhere(
		['or',
			['like', 'idtercero', '%'. $filtro . '%', false],
			['like', 'nombrecompleto', '%'. $filtro . '%', false]
		])
		->all();	
		$tercero = ArrayHelper::map( $tercero, 'idtercero', 'nombrecompleto' );	
		
		
		
		if (count($tercero) > 0)
			return json_encode($tercero);
		//se usa para evitar el error en el chosen cuando no tiene datos de respuesta
		elseif(count($tercero) == 0 )
			return '{"":""}';
		
	}



	public function actionPdf($idContrato, $anioContrato,$valorContratoletras,$sucursal = 0)
	{
		$datosContrato = $this->findModel($idContrato,$anioContrato);
		
		$connection = Yii::$app->get($_SESSION['db']);
		
		$command = $connection->createCommand("
			SELECT 	
					v.placa,
					t.idtercero, 
					t.nombrecompleto
			FROM
				vehiculos as v,
				terceros  as t,
				tbdetacontratosveh as tv
			WHERE
				tv.idContrato = $idContrato
			And
				tv.anioContrato = $anioContrato
			AND 
				tv.placa = v.placa
			AND
				v.propietario = t.idtercero
		");
		$infoVehiculo = $command->queryAll();
		
		$datosTercero = Terceros::find()->AndWhere(["idtercero"=> $datosContrato->idtercero ])->one();
		
		
		$ciudadOrigen = Tbpoblaciones::find()->AndWhere(["idCenPob" => $datosContrato->ciudadOrigen ])->one();
		$ciudadOrigen = $ciudadOrigen->Municipio . "-" . $ciudadOrigen->Departamento;
		
		
		$ciudadDestino = Tbpoblaciones::find()->AndWhere(["idCenPob" => $datosContrato->ciudadDestino ])->one();
		$ciudadDestino = $ciudadDestino->Municipio . "-" . $ciudadDestino->Departamento;
	
		
		// se fuerza la conexion para evitar que tome la conexion por defecto
		$command = $connection->createCommand(" 
			SELECT * FROM tbempresa where NitEmpresa like '%". $_SESSION['nit'] . "%'"
			);
		$infoEmpresa = $command->queryAll();
		
		$ciudadEmpresa = Tbpoblaciones::find()->AndWhere(["idCenPob" => $infoEmpresa[0]['Ciudad'] ])->one();
		$ciudadEmpresa = $ciudadEmpresa->Municipio;
		

		//fecha formateada en español
		setlocale(LC_TIME, 'es_ES.UTF-8');
		$miFecha = strtotime($datosContrato->fechaInicio);
		$fechaContrato = strftime("en %B %d, %Y", $miFecha);

		$usuario = Tbusuarios::find()->AndWhere([ "IdUsuario" => key($_SESSION['usuario']) ])->one();
		
		if ($sucursal > 0)
		{
			$sucursales = tbtercerossucursal::find()->AndWhere("idterceroSucursal = $sucursal")->all();
			$sucursales = ArrayHelper::getColumn($sucursales,'nombreSucursalTer')[0];
		}
		
		
		$datos=
		[
			"numContrato"			=> $datosContrato->nroContrato ,
			"contratista" 			=> $_SESSION['nombre'],
			"nitContratista"		=> $_SESSION['nit'],
			"contratante"			=> $datosTercero->nombrecompleto,
			"identificacion"		=> $datosTercero->idtercero . " ".$datosTercero->dv_tercero ,
			"tipoContrato"			=> $datosContrato->tipoContrato,
			"origen"				=> $ciudadOrigen,
			"destino"				=> $ciudadDestino,
			"fechaInicio"			=> substr($datosContrato->fechaInicio,0,10),
			"fechaTerminacion"		=> $datosContrato->fechaFin,
			"numePasajeros"			=> $datosContrato->nroPsj,
			"valorContrato"			=> $datosContrato->vlrContrato,
			"valorContratoletras" 	=> $valorContratoletras,
			"objetoContrato" 		=> $datosContrato->objetCont,
			"infoVehiculo" 			=> $infoVehiculo,
			"infoEmpresa"			=> $infoEmpresa[0],
			"dirContacto"			=> $datosContrato->dirResp_Contrato,
			"telContacto"			=> $datosContrato->telResp_Contrato,
			"fechaContrato" 		=> $fechaContrato,
			"ciudadEmpresa"			=> $ciudadEmpresa,
			"sucursal"				=> $sucursal,
			"sucursales"			=> $sucursales,
		
		];
		$pdf = new Pdf();
		
		$contrato = $pdf->generarPdf($datos);
		
		
		//pdf en una nueva ventana
		
		// header("Location: $contrato");
		// recipient
		$to = explode("#",$usuario->mail_Usuario)[0];
		
		// sender
		$from = 'icontroltrans@correo.com';
		
		$fromName = 'icontroltrans';
		
		// email subject
		$subject = 'Contrato'; 

		// attachment file path
		// $file = "codexworld.pdf";
		$file = $contrato;

		//email body content
		// $htmlContent = '<h1>PHP Email with Attachment by CodexWorld</h1> <p>This email has sent from PHP script with attachment.</p>';
		$htmlContent = '';

		//header for sender info
		$headers = "From: $fromName"." <".$from.">";

		//boundary 
		$semi_rand = md5(time()); 
		$mime_boundary = "==Multipart_Boundary_x{$semi_rand}x"; 

		//headers for attachment 
		$headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\""; 

		//multipart boundary 
		$message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
		"Content-Transfer-Encoding: 7bit\n\n" . $htmlContent . "\n\n"; 
		
		//preparing attachment
		if(!empty($file) > 0){
			if(is_file($file)){
				$message .= "--{$mime_boundary}\n";
				$fp =    fopen($file,"rb");
				$data =  fread($fp,filesize($file));

				@fclose($fp);
				$data = chunk_split(base64_encode($data));
				$message .= "Content-Type: application/octet-stream; name=\"".basename($file)."\"\n" . 
				"Content-Description: ".basename($file)."\n" .
				"Content-Disposition: attachment;\n" . " filename=\"".basename($file)."\"; size=".filesize($file).";\n" . 
				"Content-Transfer-Encoding: base64\n\n" . $data . "\n\n";

			}
		}
		$message .= "--{$mime_boundary}--";
		$returnpath = "-f" . $from;

		//send email
		$mail = mail($to, $subject, $message, $headers, $returnpath); 

		//email sending status
		// echo $mail?"<h1>Mail sent.</h1>":"<h1>Mail sending failed.</h1>";
		  
		return $contrato;
	}
				
    /**
     * Deletes an existing Tbcontratos model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $idContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($idContrato, $anioContrato)
    {
        $this->findModel($idContrato, $anioContrato)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Tbcontratos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $idContrato
     * @param integer $anioContrato
     * @return Tbcontratos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($idContrato, $anioContrato)
    {
        if (($model = Tbcontratos::findOne(['idContrato' => $idContrato, 'anioContrato' => $anioContrato])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
