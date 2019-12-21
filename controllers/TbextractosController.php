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
use app\models\Tbextractos;
use app\models\TbextractosBuscar;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use app\models\Tbrutas;
use app\models\Tbcontratos;
use app\models\Tbpoblaciones;
use app\models\Vehiculos;
use app\models\Tbrpbimestral;
use app\models\Terceros;
use app\models\Tbempresa;
use app\models\Tbdestinosfuec;
use app\models\Tbhv;
use app\models\Tbextractoscond;
use app\models\Pdfextractos;
use app\models\Tbusuarios;

/**
 * TbextractosController implements the CRUD actions for Tbextractos model.
 */
class TbextractosController extends Controller
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


	public $tipoContrato = 
	[
		"EMPRESARIAL" => "EMPRESARIAL -> CONTRATO PARA TRANSPORTE EMPRESARIAL",
		"ESCOLAR" => "ESCOLAR -> CONTRATO PARA TRANSPORTE DE ESTUDIENTES",
		"OCASIONAL" => "OCASIONAL -> CONTRATO PARA TRANSPORTE DE UN GRUPO ESPECIFICO DE USUARIOS",
		"SALUD" => "SALUD CONTRATO -> PARA TRANSPORTE DE USUARIOS DEL SERVICIO DE SALUD ",
		"TURISTICO" => "TURISTICO -> CONTRATO PARA TRANSPORTE DE TURISTAS",
	];
    /**
     * Lists all Tbextractos models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new TbextractosBuscar();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Tbextractos model.
     * @param integer $anioExtracto
     * @param integer $idExtracto
     * @param integer $nroContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($anioExtracto, $idExtracto, $nroContrato, $anioContrato)
    {
        return $this->render('view', [
            'model' => $this->findModel($anioExtracto, $idExtracto, $nroContrato, $anioContrato),
        ]);
    }

    /**
     * Creates a new Tbextractos model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
		
		
        $model = new Tbextractos();
		
		$rutas = $this->obtenerRutas();
		$vehiculos = $this->vehiculos();
		$departamentos = $this->departamentos();
		$variosDestinos =  $this->variosDestinos();
		
        if ($model->load(Yii::$app->request->post()) ) 
		{
			
			
			$post = Yii::$app->request->post();			
			
			$añoActual 		= date("Y");
			$datosContrato 	= explode("-",Yii::$app->request->post()['Tbextractos']['nroContrato']);
			$nroContrato 	= $datosContrato[0];
			$anioContrato 	= $datosContrato[1];
			$idExtracto 	= Tbextractos::find()->select('max(idExtracto)')->andWhere(" anioContrato = $anioContrato ")->scalar() +1; //
			$idExtracto 	= str_pad($idExtracto , 4, "0", STR_PAD_LEFT);
			
			$model->anioExtracto = $añoActual;
			
			//validacion si se ingresa algun tecto en descripDestino sin llenar el chosen de destinosVarios
			if (strlen($post['Tbextractos']['descripDestino']) > 0 )
				$destinosVarios = 1;
			else
				$destinosVarios = 0;
			
			
			$model->destinosVarios = $destinosVarios;
			
			
			$datosEmp= Tbempresa::find()->one();
			
			//Tbempresa codDirTerritorial y  nroResolucionEmp y  fechaHab y año actual(2019 solo 19) y #contrato y Tbextracto idExtracto
			$FUEC = $datosEmp->codDirTerritorial . $datosEmp->nroResolucionEmp . substr($datosEmp->fechaHab,2,2) . $añoActual . $nroContrato. $idExtracto ;
	
			$model->FUEC 		= $FUEC;		
			$model->idExtracto 	= $idExtracto;
			$model->nroContrato = $nroContrato;
			$model->anioContrato= $anioContrato ;
			// $model->anioContrato = substr($model->fechaInicio,0,4);
				
			if ( Yii::$app->request->post()['Tbextractos']['vlrServicio'] == 0) 
			{
				// $model->vlrFUEC 		= 0; 
				// $model->vlrCONTBFUEC 	= 0; 
			}
			else
			{
				$model->vlrFUEC 		= round(Yii::$app->request->post()['Tbextractos']['vlrServicio'] * ($datosEmp->porcentajeFUEC / 100) , 2); 
				$model->vlrCONTBFUEC 	= round(Yii::$app->request->post()['Tbextractos']['vlrServicio'] * ($datosEmp->contabilidadFUEC / 100) , 2); 
			}
				
			
			// $model->vlrFUEC  = $datosEmp->porcentajeFUEC / 100;
			// $model->vlrCONTBFUEC = $datosEmp->contabilidadFUEC / 100;
			
			
			$model->Aud_Usuario =key( $_SESSION['usuario'] );
			$model->Aud_Fecha = date("Y-m-d");
			
		
			
			
			if($model->save())
			{
				$contador =0;
				for ( $i = 0; $i <= count($post['conductor']) - 1; $i++ )
				{
					$conductor = $post['conductor'][$i];
					if ($conductor != 0)
					{
						
						$idhv = Tbhv::find()->AndWhere("idtercero = $conductor ")->one();
						$idhv = $idhv->idhv;
						$nombreConductor= Terceros::find()->AndWhere(["idtercero" => $conductor ])->one()->nombrecompleto;
						$conductorExtracto =  new Tbextractoscond();
						$conductorExtracto->FUEC = $FUEC;
						$conductorExtracto->idhv = $idhv;
						$conductorExtracto->licencia = $post['nroLicencia'][$i];
						$conductorExtracto->vigLicencia = $post['vigLicencia'][$i]; 
						$conductorExtracto->ultPagoSS = $post['vtoSegSocial'][$i];
						$conductorExtracto->save();
						$arraConductores[$contador]["nombre"]= $nombreConductor;
						$arraConductores[$contador]["cc"]= $conductor;
						$arraConductores[$contador]['nroLicencia']=$post['nroLicencia'][$i];
						$arraConductores[$contador]['vigLicencia']=$post['vigLicencia'][$i];
						$contador++;				
					}
				}

				$datosVehiculos 	= Vehiculos::find()->AndWhere([ "placa" => $post ['Tbextractos']['idvehiculo'] ])->one();
				$nombreContratante	= Terceros::find()->AndWhere(["idtercero" => $post['Tbextractos']['idtercero'] ])->one()->nombrecompleto;
				$objetoContrato 	= Tbcontratos::find()->andWhere(["nroContrato" => $nroContrato ."-" .$anioContrato])->one()->objetCont;
				
				$poblacionOrigen 	= Tbpoblaciones::find()->andWhere(["idCenPob" =>$post ['Tbextractos']['ciudadOrigen']  ])->one();
				$poblacionDestino 	= Tbpoblaciones::find()->andWhere(["idCenPob" =>$post ['Tbextractos']['ciudadDestino']  ])->one();
				
				
				$poblacionEmpresa	= Tbpoblaciones::find()->andWhere(["idCenPob" =>$datosEmp->Ciudad ])->one();
				 
				$departamentoDestino= $poblacionDestino->Departamento;
				$ciudadDestino		= $poblacionDestino->CentroPoblado;
				 
				
				if ( $destinosVarios > 0)
				{
				
					$ciudadDestino   = $post['Tbextractos']['descripDestino'];
					$departamentoDestino ="";
				}
				
				$datos = 
				[
					"FUEC"					=> $FUEC,
					"nombreEmpresa"			=> $_SESSION['nombre'],
					"nitEmpresa"			=> $_SESSION['nit'],
					"nroContrato" 			=> str_pad($nroContrato , 4, "0", STR_PAD_LEFT),
					"rtn"					=> $datosEmp->RNT ,
					"nombreContratante" 	=> $nombreContratante,
					"nitContratante" 		=> $post['Tbextractos']['idtercero'],
					"objetoContrato" 		=> $objetoContrato ,
					"departamentoOrigen"	=> $poblacionOrigen->Departamento,
					"ciudadOrigen" 			=> $poblacionOrigen->CentroPoblado,
					"departamentoDestino"	=> $departamentoDestino,
					"ciudadDestino" 		=> $ciudadDestino,
					"recorrido" 			=> $post['Tbextractos']['descripRuta'],
					"tipoContrato" 			=> $post['Tbextractos']['tipoContrato'],
					"convenioEmp" 			=> $post['Tbextractos']['convenioEmp'],
					"fechaInicio" 			=> $post['Tbextractos']['fechaInicio'],
					"fechaFin"	 			=> $post['Tbextractos']['fechaFin'],
					"placa"	 				=> $post['Tbextractos']['idvehiculo'],
					"interno" 				=> $datosVehiculos->NroInterno,
					"marca" 				=> $datosVehiculos->marca,
					"clase" 				=> $datosVehiculos->clase,
					"modelo" 				=> $datosVehiculos->	modelo,
					"nroTarjeta" 			=> $datosVehiculos->nroTarjOper,
					"conductores" 			=> $arraConductores,
					"responsableContrato" 	=> $post['Tbextractos']['resp_Contrato'] ,
					"cedula"  				=> $post['Tbextractos']['cedResp_Contrato'] ,
					"direccion" 			=> $post['Tbextractos']['dirResp_Contrato'] ,
					"telefono" 				=> $post['Tbextractos']['telResp_Contrato'],
					"direccionEmpresa" 		=> $datosEmp->Dirección ." Telefonos: ". $datosEmp->Telefono . " Móvil: ".$datosEmp->movil . " ". $poblacionEmpresa->Departamento ." - ". $poblacionEmpresa->CentroPoblado . " " . $datosEmp->email
				];
				
				
				$pdf = new Pdfextractos();
				$contrato = $pdf->generarPdf($datos);
				
				
				// $infoEmpresa = Tbempresa::find()->andWhere(['like', 'NitEmpresa' ,'%'. $_SESSION['nit']. '%', false])->one();
				$usuario = Tbusuarios::find()->AndWhere([ "IdUsuario" => key($_SESSION['usuario']) ])->one();
				
				// recipient
				$to = explode("#",$usuario->mail_Usuario)[0];

				// sender
				$from = 'icontroltrans@correo.com';
				$fromName = 'icontroltrans';

				// email subject
				$subject = $contrato; 

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
				$mail = @mail($to, $subject, $message, $headers, $returnpath); 

				
				$model = new Tbextractos();
				
				return $this->render('create', [
					'model' => $model,
					'rutas' => $rutas,
					'departamentos' => $departamentos, 
					'vehiculos' 	=> $vehiculos,
					'variosDestinos'=> $variosDestinos,
					'tipoContrato' 	=> $this->tipoContrato,
					'guardado' 		=> 1,
					'contrato' 		=> $contrato,
				]);
			
			}
			else
			{
				echo "Error";
				die;
			}
				
		
		}
		
        return $this->render('create', [
            'model' => $model,
			'rutas' => $rutas,
			'departamentos' => $departamentos, 
			'vehiculos' => $vehiculos,
			'variosDestinos' => $variosDestinos,
			'tipoContrato' => $this->tipoContrato,
        ]);
    }
	
	
	
	
	private function obtenerRutas()
	{
		$rutas = Tbrutas::find()->all();
		$rutas = ArrayHelper::map( $rutas, 'idRuta', 'nombreRuta' );
		
		return $rutas;
	
	}
	
	
	public function actionRutas($idRuta)
	{
		$rutas = Tbrutas::find()->andWhere("idRuta = $idRuta")->all();
		$rutas = ArrayHelper::getColumn( $rutas, 'decripcionRuta' );
		
		return json_encode($rutas);
	}
	
	
	public function actionContratos($idtercero)
	{
		
		// $contratos = Tbcontratos::find()->andWhere("estado ='ACTIVO' and idtercero = '$idtercero'" )->all();
		$contratos = Tbcontratos::find()->andWhere("estado ='ACTIVO' and idtercero = '$idtercero' and fechaFin >= CURDATE()" )->all();
		$contratos = ArrayHelper::map( $contratos, 'nroContrato','aliasContrato' );
		$datosContrato=[];
		foreach ($contratos as $key =>  $aliasContrato)
		{
			$datosContrato[ $key ] = $key . " " .  $aliasContrato;
		}
		return json_encode( $datosContrato );
		
	}
	
	private function departamentos()
	{
	
		$departamentos = Tbpoblaciones::find()->select("Departamento")->groupBy("Departamento")->all();
		$departamentos = ArrayHelper::map($departamentos,"Departamento","Departamento");
		
		return $departamentos;
	
	}
	
	
	public function actionCiudad($idCenPob)
	{
		$departamentos = Tbpoblaciones::find()->andWhere("idCenPob = $idCenPob")->all();
		$departamentos = ArrayHelper::map($departamentos,"idCenPob","CentroPoblado");
		
		return json_encode( $departamentos );
	}
	
	
	public function actionCiudades($idPais,$idCenPob)
	{
		
		if (is_numeric($idPais))
		{
			$datosCiudades = Tbpoblaciones::find()->andWhere("idCenPob = '$idCenPob'")->all();
			$datosCiudades = ArrayHelper::toArray($datosCiudades);
			
			foreach ($datosCiudades as $ciudad)
			{
				$ciudades[$ciudad['idCenPob']] =  $ciudad['CentroPoblado'] . "-" . $ciudad['Municipio'] . "-" . $ciudad['Departamento'];
			}
			
			return json_encode($ciudades);
		}
	}
	
	
	public function actionInfoContrato($nroContrato)
	{
		$datosContrato = explode("-",$nroContrato);
		$anio = date("Y");
		$contratos = Tbcontratos::find()->andWhere(" nroContrato =" . $datosContrato[0] ." and anioContrato =" . $datosContrato[1] ."  ")->all();
		$contratos = ArrayHelper::toArray( $contratos );
		
		$contabilidadFuec = Tbempresa::find()->andWhere([ "Nombre" =>$_SESSION['nombre'] ])->all();
		$contabilidadFuec = ArrayHelper::getColumn( $contabilidadFuec,"contabilidadFUEC" ); 
		
		$contratos[0]['contabilidadFuec'] = $contabilidadFuec[0]/100;
		
		return json_encode( $contratos[0] );
		
	}
	
	//todos los vehiculos 
	private function vehiculos()
	{
		$infoVehiculos = Vehiculos::find()->all();
		$infoVehiculos = ArrayHelper::map( $infoVehiculos,'placa','NroInterno' );
		$vehiculos = [];
		
		foreach ($infoVehiculos as $key => $info)
			$vehiculos[$key] = "placa ". $key ." - lateral ". $info; 
			
		return $vehiculos;
	}
	
	public function actionDocVehiculos($placa)
	{
		$infoVehiculos = Vehiculos::find()->andWhere(["placa" => $placa])->one();
		
		// $infoVehiculosEmpAfil =  ArrayHelper::map($infoVehiculos,'emprAfil','emprAfil');
		$infoVehiculosEmprAfil= $infoVehiculos;
		
		$infoVehiculos = ArrayHelper::getValue($infoVehiculos, function ($infoVehiculos, $defaultValue) {
			
			$arrayInfo['fechaVtoTO']['nombre'] = "Tarjeta Operación";
			$arrayInfo['fechaVtoTO']['fecha'] = substr($infoVehiculos->fechaVtoTO,0,10);
			
			$arrayInfo['fechaVtoExtintor']['nombre'] = "Extintor";
			$arrayInfo['fechaVtoExtintor']['fecha'] = substr($infoVehiculos->fechaVtoExtintor,0,10);
			
			$arrayInfo['fechaVtoCDA']['nombre'] = "CDA";
			$arrayInfo['fechaVtoCDA']['fecha'] = substr($infoVehiculos->fechaVtoCDA,0,10);
			
			$arrayInfo['fechaVtoSOAT']['nombre'] = "SOAT";
			$arrayInfo['fechaVtoSOAT']['fecha'] = substr($infoVehiculos->fechaVtoSOAT,0,10);
			
			$arrayInfo['fechaVtoRCC']['nombre'] = "RCC";
			$arrayInfo['fechaVtoRCC']['fecha'] = substr($infoVehiculos->fechaVtoRCC,0,10);
			
			$arrayInfo['fechaVtoRCE']['nombre'] = "RCE";
			$arrayInfo['fechaVtoRCE']['fecha'] = substr($infoVehiculos->fechaVtoRCE,0,10);
				
			return $arrayInfo;
		});
		
		$vehEstado = [];
		
		//llenar el array con la informacion del estado actual de los documentos del vehiculo
		foreach ($infoVehiculos as $vehi)
			$vehEstado[ $vehi['nombre'] ]= $this->fechaVencida($vehi['nombre'],$vehi['fecha']);
		
		$vtoBimestral = Tbrpbimestral::find()->AndWhere("placa = '$placa'")->max('fechaVtoRPbimest');		
		
			
		//dar formato a las fechas vencidas
		foreach ($infoVehiculos as $key => $vehi)
			$infoVehiculos[$key]['fecha'] = $this->vencimientofecha($vehi['fecha']);
		
		$infoVehiculos['fechaVtoRPbimest']['nombre'] ="Revisión Bimestra";
		$infoVehiculos['fechaVtoRPbimest']['fecha'] = $this->vencimientofecha(substr($vtoBimestral,0,10));
		
		$fechasVencidas = [];
		foreach ($infoVehiculos as $info)
		{
			if (strpos ( $info['fecha'] , "vencido" ) > 0)
			{
				$fechasVencidas[] = $info['nombre'];
			}
			else
			{
				$fechas[] = $info['fecha'];
				$nombres[] = $info['nombre'];
			}
			
		}
			
		//saber si tiene alguna fecha vencida o cual es la mas proxima a vencerse
		$estadoDoc ="";
		if (count ($fechasVencidas) > 0)
		{
			foreach ($fechasVencidas as $fv)
			{
				$estadoDoc .= $fv ." vencido <br>";
			}
		}
		else
		{
			date_default_timezone_set('America/Bogota');
			$datetime1 = date_create( date("Y-m-d") );
			$datetime2 = date_create(min($fechas));
			$intervalDias = date_diff($datetime1, $datetime2);
			$intervalDias = " " .$intervalDias->format("%a") . " días para vencerse";
			
			$estadoDoc = $nombres[array_search (min($fechas),$fechas)]. $intervalDias ;
		
		}

		
		$infoVehiculos['estadoDocumentos'] = $estadoDoc;
		
		
		
		
		$infoVehiculos['emprAfil']['emprAfil'] = $infoVehiculosEmprAfil->emprAfil;
		$infoVehiculos['emprAfil']['fechaVtoConvenio'] = $infoVehiculosEmprAfil->fechaVtoConvenio;
		
		//tipo de vehiculo clase
		$infoVehiculos['clase'] = $infoVehiculosEmprAfil->claseTarifaFUEC;
		
		
		
		
		return json_encode( $infoVehiculos );
	}

	private function fechaVencida($nombrec,$fecha)
	{
		date_default_timezone_set('America/Bogota');
		if (date("Y-m-d") >  $fecha )
		{
			return "$nombrec vencido";
		}
		else
		{
			$datetime1 = date_create( date("Y-m-d") );
			$datetime2 = date_create($fecha);
			$intervalDias = date_diff($datetime1, $datetime2);
			$intervalDias = $nombrec . " " .$intervalDias->format("%a") . " dias Para vencerse";
			return $intervalDias;
			
		}
	}
	
	private function vencimientofecha($fecha)
	{
		date_default_timezone_set('America/Bogota');
		if(strlen($fecha) == 0)
			return "No posee";
		elseif (date("Y-m-d") >  $fecha )
			return $fecha.'vencido';
		else
			return $fecha;
		
	}
	
	
	public function actionInfoResponsable($idtercero,$btn)
	{
		
		$tercero = Terceros::find()->AndWhere("idtercero = $idtercero")->all();
		$tercero = ArrayHelper::toArray($tercero)[0];
		
		$infoinfoContacto = [];
		if($btn == "btnTercero")
		{
			$infoContacto['nombre'] 		= $tercero['nombrecompleto'];
			$infoContacto['identificacion'] = $tercero['idtercero'];
			$infoContacto['direccion'] 		= $tercero['direccion_tercero'];
			$infoContacto['movil'] 			= $tercero['movil_tercero'];
			
		}
		elseif($btn == "btnConTercero")
		{
			$infoContacto['nombre'] 		= $tercero['contacto_tercero'];
			$infoContacto['identificacion'] = $tercero['ced_Contacto'];
			$infoContacto['direccion'] 		= $tercero['dir_contacto'];
			$infoContacto['movil'] 			= $tercero['tel_contacto'];
			
		}
		
			return json_encode($infoContacto);
	}
	
	public function actionConductores($placa)
	{
		
		$connection = Yii::$app->get($_SESSION['db']);
		$command = $connection->createCommand("
		SELECT 
			t.nombrecompleto,
			h.idtercero,
			h.licencia,
			MAX(h.vigLicencia) as vigLicencia,
			MAX(s.vtoSegSocial) as vtoSegSocial
		FROM 
			tbdetaconductores AS c, 
			tbhv AS h, 
			terceros AS t, 
			tbsegsocial AS s
		WHERE 
			c.idVeh = '$placa'
		AND
			c.idhv = h.idhv
		AND 
			h.estado = 'ACTIVO'
		AND
			s.idhv = h.idhv
		AND 
			h.idtercero = t.idtercero
		AND 
			h.hvBloqueado = 0
			
		GROUP BY h.idtercero
		");
		$result = $command->queryAll();
		
		return json_encode($result); 
		// return $result;
		
	}

	private function variosDestinos()
	{
		$variosDestinos = Tbdestinosfuec::find()->all();
		$variosDestinos = ArrayHelper::map($variosDestinos,'idDestinoFUEC','nombreDestinoFUEC');
		
		return $variosDestinos;
	}
	
	public function actionDecripcionDestino($idDestinoFUEC)
	{
		$descripcion = Tbdestinosfuec::find()->andWhere("idDestinoFUEC = $idDestinoFUEC")->all();
		$descripcion = ArrayHelper::getColumn($descripcion,'decripcionDestinoFUEC','');
		
		return json_encode ($descripcion[0]);
	}
	
	
    /**
     * Updates an existing Tbextractos model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $anioExtracto
     * @param integer $idExtracto
     * @param integer $nroContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($anioExtracto, $idExtracto, $nroContrato, $anioContrato)
    {
        $model = $this->findModel($anioExtracto, $idExtracto, $nroContrato, $anioContrato);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'anioExtracto' => $model->anioExtracto, 'idExtracto' => $model->idExtracto, 'nroContrato' => $model->nroContrato, 'anioContrato' => $model->anioContrato]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }
	
	
	

    /**
     * Deletes an existing Tbextractos model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $anioExtracto
     * @param integer $idExtracto
     * @param integer $nroContrato
     * @param integer $anioContrato
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($anioExtracto, $idExtracto, $nroContrato, $anioContrato)
    {
        $this->findModel($anioExtracto, $idExtracto, $nroContrato, $anioContrato)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Tbextractos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $anioExtracto
     * @param integer $idExtracto
     * @param integer $nroContrato
     * @param integer $anioContrato
     * @return Tbextractos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($anioExtracto, $idExtracto, $nroContrato, $anioContrato)
    {
        if (($model = Tbextractos::findOne(['anioExtracto' => $anioExtracto, 'idExtracto' => $idExtracto, 'nroContrato' => $nroContrato, 'anioContrato' => $anioContrato])) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
	
	public function actionFuecAnterior($fuecAnt)
	{
		

		$connection = Yii::$app->get($_SESSION['db']);
		$command = $connection->createCommand("
			SELECT 
			tbter.nombrecompleto,
			tbter.idtercero,
			tbext.resp_Contrato,
			tbext.cedResp_Contrato,
			tbext.dirResp_Contrato,
			tbext.telResp_Contrato,
            tbext.ciudadOrigen,
            tbext.ciudadDestino,
            tbext.descripRuta
			
		FROM 
			tbextractos as tbext, terceros as tbter
		WHERE 
			tbext.FUEC = '$fuecAnt'

		AND
			tbext.idtercero = tbter.idtercero
			
		
		");
		$result = $command->queryAll();
		
		if(!empty($result)){
			//consulta ciudad origen  1765
			$command = $connection->createCommand("SELECT `CentroPoblado` FROM `tbpoblaciones` WHERE `idCenPob`=".$result[0]['ciudadOrigen']);
			$ciudadOrigen = $command->queryAll();
			// $result[0]['ciudadOrigen']=$ciudadOrigen[0]['CentroPoblado'];
			if(!empty($ciudadOrigen)){$result[0]['idciudadOrigen']=$result[0]['ciudadOrigen'];}
			
			//consulta ciudad destino  
			$command = $connection->createCommand("SELECT `CentroPoblado` FROM `tbpoblaciones` WHERE `idCenPob`=".$result[0]['ciudadDestino']);
			$ciudadDestino = $command->queryAll();
			
			// $result[0]['ciudadDestino']=$ciudadDestino[0]['CentroPoblado'];
			if(!empty($ciudadDestino)){$result[0]['idciudadDestino']=$result[0]['ciudadDestino'];}
		}
		else{
			$result[0]="";
		}
		
		return json_encode($result[0]); 

		
	}
}
