$( document ).ready(function() 
{
	
	$("[data-select2-id='tbextractos-idtercero']").prop( "disabled", false );
	// $("[data-select2-id='tbextractos-idtercero']").prop( "disabled", false );
	if( '/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i'.test(navigator.userAgent) ) 
	{
		console.log('Esto es un dispositivo móvil');
		$("[data-select2-id='tbextractos-idtercero']").prop( "disabled", true );
		
	}
	
	$("#tbextractos-descripdestino").parent().toggle();
	$("#w0").on("beforeSubmit", function()
	{
		contador =0;
		$("[id^='conductor-']").each(function( index ) 
		{
			if ( $(this).val() != 0 )
			{
				contador = 1;
			}
			
		});
		
		if (contador == 0)
		{
			Swal.fire(
				{
				  title: 'Seleccione un Conductor',
				  type: 'info',
				  focusConfirm: false,
				  confirmButtonText:
					'Aceptar'
				});
			return false;
		}
		else
		{
			$.blockUI({ message: '<img src="../web/images/procesando1.gif" height="60px" width="60px"/>' });
		}
		
		
	});	
	
});

//informacion del contrato
$("#tbextractos-nrocontrato").change(function() 
{
	$("#tbextractos-fechafin").val("");
	filtro = $(this).val();
	$.get( 'index.php?r=tbextractos/info-contrato&nroContrato='+filtro,
			function( data )
			{
				
				datosContrato = data;				
				//responsable contrato 
				$("#tbextractos-resp_contrato").val(data.resp_Contrato);
				//cedula contacto 
				$("#tbextractos-cedresp_contrato").val(data.cedResp_Contrato);
				//direccion contacto 
				$("#tbextractos-dirresp_contrato").val(data.dirResp_Contrato);
				// telefono contacto
				$("#tbextractos-telresp_contrato").val(data.telResp_Contrato);
				//fecha inicio contrato 
				$("#tbextractos-fechainicio").val( fechaActual() );
				//fecha fin contrato 
				datoFechaFin = data.fechaFin.substr(0, 10);
				
				
				
				//ciudad origen contrato 
				obj = $("#tbextractos-ciudadorigen");
				poblaciones(data.ciudadOrigen,obj);
				
				//ciudad origen contrato 
				obj = $("#tbextractos-ciudaddestino");
				poblaciones(data.ciudadDestino,obj);
				
				$("#tbextractos-fechainicio").trigger("change");
				//tipoContrato
				$("#tbextractos-tipocontrato").val(data.tipoContrato);
				$("#tbextractos-tipocontrato").trigger("chosen:updated");
				//valor contrato 
				// $("#tbextractos-vlrservicio").val(data.vlrContrato);
				$("#tbextractos-vlrfuec").val(data.vlrContrato * data.contabilidadFuec);
				
				// Modo de uso: 500,34 USD
				valor = $("#tbextractos-vlrservicio").val();
				letras = numeroALetras(valor, {
				  plural: 'PESOS',
				  singular: 'PESO',
				  centPlural: 'CENTAVOS',
				  centSingular: 'CENTAVO'
				});

				$("#vlrLetras").val(letras + "M/CTE");
					
			},'json'
				);
	
});

//validaciones de las fechas de vencimientos de los documentos del vehiculo
$("#tbextractos-idvehiculo").change(function() 
{

	//elemento que se llenan
	vehvtoto = $("#tbextractos-vehvtoto");
	vehvtoextintor = $("#tbextractos-vehvtoextintor");
	vehvtocda = $("#tbextractos-vehvtocda");
	vehvtosoat = $("#tbextractos-vehvtosoat");
	vehvtorcc = $("#tbextractos-vehvtorcc");
	vehvtorce = $("#tbextractos-vehvtorce");
	vehvtobimestral = $("#tbextractos-vehvtobimestral");
	
	
	filtro = $(this).val();
		
	if (filtro == "" )
	{
		vehvtoto.val("");
		vehvtoto.attr("style","");
		vehvtoextintor.val("");
		vehvtoextintor.attr("style","");
		vehvtocda.val("");
		vehvtocda.attr("style","");
		vehvtosoat.val("");
		vehvtosoat.attr("style","");
		vehvtorcc.val("");
		vehvtorcc.attr("style","");
		vehvtorce.val("");
		vehvtorce.attr("style","");
		vehvtobimestral.val("");
		vehvtobimestral.attr("style","");
	}
	else
	{
	
	$.get( 'index.php?r=tbextractos/doc-vehiculos&placa='+filtro,
		function( data )
		{
			$("#claseVehiculo").val(data.clase);
			
			if( data.emprAfil.emprAfil != nombreEmpresa)
			{
				$("#tbextractos-convenioemp").val(data.emprAfil.emprAfil);
				
				if (data.emprAfil.fechaVtoConvenio === null) 
				{
					$("#tbextractos-fechavtoconvenio").val("Sin datos");
				}
				else
				{
					//
				}
			}
			

			//validaciones de fecha 
			arrayfechas =[];
			arrayfechas[0] = vechasVencidas(data.fechaVtoTO.fecha,vehvtoto);
			arrayfechas[1] = vechasVencidas(data.fechaVtoExtintor.fecha,vehvtoextintor);
			arrayfechas[2] = vechasVencidas(data.fechaVtoCDA.fecha,vehvtocda);
			arrayfechas[3] = vechasVencidas(data.fechaVtoSOAT.fecha,vehvtosoat);
			arrayfechas[4] = vechasVencidas(data.fechaVtoRCC.fecha,vehvtorcc);
			arrayfechas[5] = vechasVencidas(data.fechaVtoRCE.fecha,vehvtorce);
			arrayfechas[6] = vechasVencidas(data.fechaVtoRPbimest.fecha,vehvtobimestral);
			console.log(data.emprAfil);
			if (data.emprAfil.fechaVtoConvenio != null)
			{
				$("#tbextractos-fechavtoconvenio").val(data.emprAfil.fechaVtoConvenio.substr(0,10));
			}
			
			
			Swal.fire(
			{
			  title: ''+ data.estadoDocumentos,
			  type: 'info',
			  focusConfirm: false,
			  confirmButtonText:
				'Aceptar'
			  
			}).then((result) => 
			{
				
			  if (data.estadoDocumentos.indexOf("vencido") > 1) 
			  {
				location.reload();
			  }
			});
	
		},'json'
			);
	}
});

//valida si la fecha esta vencida y aplica el atributo 
function vechasVencidas(fecha, obj)
{
	estilo ="background-color:  #a93226;  color: white; border-radius: 5px;";
	obj.attr("style","");
	if (fecha.substr(10) == "vencido" || fecha.substr(0,8) == "No posee") 
	{
		obj.attr("style",estilo);
		obj.val(fecha.substr(0,10));
		return 1;
	}
	else
	{
		 obj.val(fecha);
		 return 0;
	}
	
}


$("#btnTercero,#btnConTercero ").click(function() 
{
	// alert();
	idtercero = $("#tbextractos-idtercero").val();
	if (idtercero == null)
	{
		Swal.fire(
		{
		  title: 'Seleccione un tercero',
		  type: 'info',
		  focusConfirm: false,
		  confirmButtonText:
			'Aceptar'
		})
	}
	else
	{
		$.get( 'index.php?r=tbextractos/info-responsable&idtercero='+idtercero+"&btn="+$(this).attr("id"),
		function( data )
		{						
			//responsable contrato 
			$("#tbextractos-resp_contrato").val(data.nombre);
			//cedula contacto 
			$("#tbextractos-cedresp_contrato").val(data.identificacion);
			//direccion contacto 
			$("#tbextractos-dirresp_contrato").val(data.direccion);
			// telefono contacto
			$("#tbextractos-telresp_contrato").val(data.movil);
		},'json'
			);
	}

});

//validaciones de las fechas de vencimientos de los documentos del conductor 
$("#tbextractos-idvehiculo").change(function() 
{
	placa = $(this).val();
	vehiculo = $(this);
	$("#claseVehiculo").val("");
	$("#tbextractos-convenioemp").val("");
	$("#tbextractos-fechavtoconvenio").val("");
	$.get( 'index.php?r=tbextractos/conductores&placa='+placa,
	function( data )
	{
		stilo ="background-color:  #a93226;  color: white; border-radius: 5px;";
		if (data.length > 0)
		{
			datos = data;
			html ="";
			html ='<fieldset class="scheduler-border">';
			html +='<legend class="scheduler-border">Conductores</legend>';
			
			
			if (data.length > 6)
				tamaño = 6;
			else
				tamaño = data.length;
			
			for (i = 0; i < tamaño; i++) 
			{
			  
				html +='<div class="row"  >';
				html +='<div class="col-md-6"><label> Nombre conductor </label><select class="form-control" style="width: 95%;" name="conductor[]" id ="conductor-'+i+'" onchange="validarFechasConductor(this)" ><option value="0"> </option>';
				
				
				$.each( data, function( key, value ) 
				{
					vtoSegSocial=value.vtoSegSocial.substr(0,10);
					vigLicencia=value.vigLicencia.substr(0,10);
					html += '<option value="'+ value.idtercero+ '">'+value.nombrecompleto+'</option>';
					
				});	
				
				html +='</select></div>';
				html +='<div class="col-md-2"><label> Nro licencia </label><input class="form-control" type="text"  value = "" name="nroLicencia[]" id="nroLicencia-'+i+'"   readOnly ></div>';
				html +='<div class="col-md-2"><label>Vig Licencia</label> <input class="form-control" type="text" name="vigLicencia[]" id="vigLicencia-'+i+'" readOnly  ></div>';
				html +='<div class="col-md-2"><label> Vig Seg Social</label> <input class="form-control" type="text" name="vtoSegSocial[]" id="vtoSegSocial-'+i+'" readOnly ></div>';
				html +='</div>';
				html +='<div id="infoCoductores"> </div>';
				
			}
			
			html +='</fieldset>';
		}
		else
		{
			html ="";
			html ='<fieldset class="scheduler-border">';
			html +='<legend class="scheduler-border">Conductores</legend>';
			html +='<div class="row">';
			html +='<div class="col-md-6"><label> Nombre conductor </label><select class="form-control" style="width: 95%;"><option value="0"></option>';
			html +='<option value="">Sin datos</option>';
			html +='</select></div>';
			html +='<div class="col-md-2"><label> Nro licencia </label><input class="form-control" type="text"  value = "" readOnly ></div>';
			html +='<div class="col-md-2"><label> Vig Seg Social</label> <input class="form-control" type="text" name="vtoSegSocial" value = "" readOnly ></div>';
			html +='<div class="col-md-2"><label>Vig Licencia</label> <input class="form-control" type="text" name="vigLicencia" value = "" readOnly  ></div>';
			html +='</fieldset>';	
		}
		
		$("#conductores").html(html);
		
	},'json'
		);

});



// se llenar las rutas en el campo Descripción Ruta del formulario
$(" #tbextractos-idruta ").change(function() 
{
	
	idRuta = $(this).val();
	if(idRuta > 0)
	{
		$.get( 'index.php?r=tbextractos/rutas&idRuta='+idRuta,
		function( data )
		{
			// descripruta = descripruta + data;
			$("#tbextractos-descripruta" ).val(data);
			
		},'json'
			);
	}
	else
	{
		$("#tbextractos-descripruta" ).val("");
	}
});

//identificacion del tercero o contratante 
$(" #tbextractos-idtercero ").change(function() 
{
	$(" #docTercero").val($(this).val());
	filtro = $(this).val();
	info ='';
	$.get( 'index.php?r=tbextractos/contratos&idtercero='+filtro,
					function( data )
					{
						$.each(data, function( index, datos) 
							{	
								info = info + '<option value='+index+'>'+datos+'</option>';
								
							});
							// alert(data);
						select = $('#tbextractos-nrocontrato');	
						select.html('');
						select.trigger('chosen:updated');
						
						select.append(info);
						select.trigger('chosen:updated');
						
					},'json'
						);
});


function fechaActual()
{
	
	var d = new Date();

	var month = d.getMonth()+1;
	var day = d.getDate();

	var fecha = d.getFullYear() + '-' +
		(month<10 ? '0' : '') + month + '-' +
		(day<10 ? '0' : '') + day;

	return fecha;
	
}


function validarFechasConductor(obj)
{
	mensaje = "";
	id = $(obj).attr("id").split("-")[1];
	if (obj.value == 0 )
	{
		$("#nroLicencia-"+id).val("");
		$("#vtoSegSocial-"+id).val("");
		$("#vigLicencia-"+id).val("");
	}
	else
	{
		//se recorre el array para extraer los datos correspondientes
		$.each( datos, function( key, value ) 
		{
			if (value.idtercero == obj.value)
			{
				vtoSegSocial=value.vtoSegSocial.substr(0,10);
				vigLicencia=value.vigLicencia.substr(0,10);
			}
		});	
					
		fechaFin = $("#tbextractos-fechafin").val();
		
		if(fechaFin == "")
		{
			Swal.fire({
			  title: 'Fecha fin',
			  text: "No puede estar vacia",
			  type: 'warning',
			  allowOutsideClick: false,
			  allowEscapeKey: false,
			  confirmButtonColor: '#3085d6',
			  confirmButtonText: 'Aceptar',
				}).then((result) => {
				  if (result.value) 
				  {
					$("#conductor-"+id+"").val(0);
				  }
				})
		}
		else
		{
			if (fechaActual() > vtoSegSocial )
			{
				mensaje += "Seguro Vencido <br>";
				$("#conductor-"+id+"").val(0);
			}
			
			if ( fechaActual() > vigLicencia )
			{
				mensaje += "Licencia Vencida<br>";
				$("#conductor-"+id+"").val(0);
			}
			
			
			if ( fechaFin > vigLicencia )
			{
				mensaje += "La vigencia de la licencia sobrepasa la fecha fin <br>"; 
				$("#conductor-"+id+"").val(0);
			}
			
			if(mensaje =="")
			{
				
				if (fechaFin > vtoSegSocial )
					mensaje += "La vigencia del seguro social sobrepasa la fecha fin <br>";
				
				//dias vencimiento seguro social
				var fechaInicio = new Date( fechaActual() ).getTime();
				var fechaFin    = new Date( vtoSegSocial ).getTime();
				var diff = (fechaFin - fechaInicio)/(1000*60*60*24);
				mensaje += "La seguridad vence en "+diff+" días <br> ";
				
				//dias vencimiento licencia
				var fechaInicio = new Date( fechaActual() ).getTime();
				var fechaFin    = new Date( vigLicencia ).getTime();
				var diff = (fechaFin - fechaInicio)/(1000*60*60*24);
				mensaje += "La licencia vence en "+diff+" días <br> ";
				
				
				$("#nroLicencia-"+id).val(datos[id]['licencia']);
				$("#vtoSegSocial-"+id).val(vtoSegSocial);
				$("#vigLicencia-"+id).val(vigLicencia);
				
			}
			
			Swal.fire(
			{
			  title: ''+ mensaje,
			  type: 'info',
			  focusConfirm: false,
			  confirmButtonText:
				'Aceptar'
			  
			});
		
		}
	}
}

//llenar las ciudadades de orien segun el departamento seleccionado
$("[name='departamentoCiudadOrigen']").change(function() 
{
	departamento = $(this).val();
	
	var opcionesCiudad = "";
		idPais = 169;
		$.get( "index.php?r=terceros/ciudades&idPais="+idPais+"&departamento="+departamento,
				function( data )
				{
					$.each(data, function( index, datos) 
						{	
							opcionesCiudad = opcionesCiudad + '<option value="'+index+'">'+datos+'</option>';
						});
						
					chosen = $("#tbextractos-ciudadorigen");
					
					chosen.html("");	
					chosen.trigger("chosen:updated");	
					
					chosen.append(opcionesCiudad);
					chosen.trigger("chosen:updated");
					
						
				},"json"
			);
	
	
});

$("[name='departamentoCiudadDestino']").change(function() 
{
	departamento = $(this).val();
	
	var opcionesCiudad = "";
		idPais = 169;
		$.get( "index.php?r=terceros/ciudades&idPais="+idPais+"&departamento="+departamento,
				function( data )
				{
					$.each(data, function( index, datos) 
						{	
							opcionesCiudad = opcionesCiudad + '<option value="'+index+'">'+datos+'</option>';
						});
						
					chosen = $("#tbextractos-ciudaddestino");
					
					chosen.html("");	
					chosen.trigger("chosen:updated");	
					
					chosen.append(opcionesCiudad);
					chosen.trigger("chosen:updated");
					
				},"json"
			);
	
	
});

function poblaciones(idCenPob,obj)
{
	
	pob = "";
	idPais = 169;
	$.get( "index.php?r=tbextractos/ciudades&idPais="+idPais+"&idCenPob="+idCenPob,
			function( data )
			{
				pob = "";
				$.each(data, function( index, datos) 
					{	
						pob = pob + '<option value="'+index+'">'+datos+'</option>';
						
						//llenar select de ciudad
						chosen = $(obj);
						chosen.html("");	
						chosen.trigger("chosen:updated");	
						chosen.append( pob );
						chosen.val(index);
						chosen.trigger("chosen:updated");
					});
					
				
					//llenar departamento orien
				if($(obj).attr("id").indexOf("origen") > -1)
				{
					departamento = pob.split("-")[2].split("<")[0];
					// $( "input[name='departamentoCiudadOrigen']" ).val(departamento);
					departamentoOrigen= $( "#w1" );
					
					departamentoOrigen.val(departamento);
					departamentoOrigen.trigger("chosen:updated");
				}
				
				//llenar departamento destino
				if($(obj).attr("id").indexOf("destino") > -1)
				{
					departamento = pob.split("-")[2].split("<")[0];
					// $( "input[name='departamentoCiudadOrigen']" ).val(departamento);
					departamentoOrigen= $( "#w2" );
					
					departamentoOrigen.val(departamento);
					departamentoOrigen.trigger("chosen:updated");
				}
				
			},"json"
		);
	
}


//calcular la diferencia de dias entre fechainicio y fecha fin
$("#tbextractos-fechainicio, #tbextractos-fechafin").change(function() 
{

	inicio = $("#tbextractos-fechainicio").val();
	fin = $("#tbextractos-fechafin").val();
	var fechaini = new Date(inicio);
	var fechafin = new Date(fin);
	var diasdif= fechafin.getTime()-fechaini.getTime();
	var contdias = Math.round(diasdif/(1000*60*60*24));
	
	
	if (isNaN(contdias))
	{
		
	}
	else
	{
		if (contdias  < 0)
		{
			$("#tbextractos-fechafin").val("");
			Swal.fire(
			{
			  title: 'La Fecha Fin mayor o igual Fecha Inicio',
			  type: 'info',
			  focusConfirm: false,
			  confirmButtonText:
				'Aceptar'
			});
			
		}
		else
		{	
			$("#diasExtractos").val(contdias + 1);
		}
	
	}
	
	
});


//varios destino 
$("#tbextractos-destinosvarios").change(function() 
{
	// $("#w2_chosen").parent().append('<select id="variosDestinos" >  <option value="volvo">Volvo</option>  </select>');
	$("#variosDestinos").toggle();
	$("#w2_chosen").toggle();
	$("#w2_chosen").siblings("label").toggle();
	$(".field-tbextractos-ciudaddestino").toggle();
	$("#tbextractos-descripdestino").parent().toggle();
	
	//si no esta checked borrar los datos
	if (!$(this).checked) 
	{
		$("#tbextractos-descripdestino").val("");
		$("#idVariosDestinos").val("");
	}
 
});



//varios destino 
$("#idVariosDestinos").change(function() 
{
	idDestinoFUEC = $(this).val();
	$.get( "index.php?r=tbextractos/decripcion-destino&idDestinoFUEC="+idDestinoFUEC,
			function( data )
			{
				$("#tbextractos-descripdestino").val(data);
			},"json"
		);
});


//validaciones fecha fin vs fecha vehiculos y fecha fin contrataro no puede ser mayor
$('#tbextractos-fechafin').on('input change',function(e){
    placa = $("#tbextractos-idvehiculo").val();
    contrato = $("#tbextractos-nrocontrato").val();
	
	fechfin = $(this);
	
	fechaExtracto  = fechfin.val();
	if(placa == null || contrato == null)
	{
		Swal.fire(
		{
		  title: 'Selecciones una "Placa - Lateral" y N° Contrato',
		  type: 'info',
		  focusConfirm: false,
		  confirmButtonText:
			'Aceptar'
		});
		fechfin.val("");
	}
	else
	{
		var fechas = [
			$("#tbextractos-vehvtoto").val(),
			$("#tbextractos-vehvtoextintor").val(),
			$("#tbextractos-vehvtocda").val(),
			$("#tbextractos-vehvtosoat").val(),
			$("#tbextractos-vehvtorcc").val(),
			$("#tbextractos-vehvtorce").val(),
			$("#tbextractos-vehvtobimestral").val() ,
			datoFechaFin,
			$("#tbextractos-fechavtoconvenio").val(),
		];
		
		
		nombres = [
		"OPERACIÓN",
		"EXTINTOR",
		"CDA",
		"SOAT",
		"RCC",
		"RCE",
		"BIMESTRAL",
		"FIN CONTRATO",
		"CONVENIO",
		];
		
		
		fechaini = new Date(fechaExtracto);
		$.each( fechas, function( key, value ) 
		{
		
			fechafin = new Date(value);
			diasdif= fechafin.getTime()-fechaini.getTime();
			contdias = Math.round(diasdif/(1000*60*60*24));
			
			if (isNaN(contdias))
			{
				
			}
			else
			{
				if (contdias < 0 )
				{
					// Swal.fire(
					// {
					  // title: 'Fecha incorrecta',
					  // text: 'La fecha ' + nombres[key] + ' no debe sobrepasar la fecha fin del extracto',
					  // type: 'info',
					  // focusConfirm: false,
					  // confirmButtonText:
						// 'Aceptar'
					// });
					
					Swal.fire(
					{
					  title: 'Fecha incorrecta',
					  text: 'La fecha fin del extracto FUEC sobrepasa la fecha de vencimiento del ' + nombres[key],
					  type: 'info',
					  focusConfirm: false,
					  confirmButtonText:
						'Aceptar'
					});
					
					fechfin.val("");
					return false;
				}
			}
				
		});
	}
	
	
	
	
	
});



$("#tbextractos-nrocontratos").change(function() 
{
	// Modo de uso: 500,34 USD
	valor = $("#tbextractos-vlrservicio").val();
	letras = numeroALetras(valor, {
	  plural: 'PESOS',
	  singular: 'PESO',
	  centPlural: 'CENTAVOS',
	  centSingular: 'CENTAVO'
	});

	$("#vlrLetras").val(letras + "M/CTE");
	
		
});

$("#tbextractos-vlrservicio").keyup(function() 
{
	// Modo de uso: 500,34 USD
	valor = $("#tbextractos-vlrservicio").val();
	letras = numeroALetras(valor, {
	  plural: 'PESOS',
	  singular: 'PESO',
	  centPlural: 'CENTAVOS',
	  centSingular: 'CENTAVO'
	});

	$("#vlrLetras").val(letras + "M/CTE");
	
		
});




var numeroALetras = (function() {

// Código basado en https://gist.github.com/alfchee/e563340276f89b22042a
    function Unidades(num){

        switch(num)
        {
            case 1: return 'UN';
            case 2: return 'DOS';
            case 3: return 'TRES';
            case 4: return 'CUATRO';
            case 5: return 'CINCO';
            case 6: return 'SEIS';
            case 7: return 'SIETE';
            case 8: return 'OCHO';
            case 9: return 'NUEVE';
        }

        return '';
    }//Unidades()

    function Decenas(num){

        let decena = Math.floor(num/10);
        let unidad = num - (decena * 10);

        switch(decena)
        {
            case 1:
                switch(unidad)
                {
                    case 0: return 'DIEZ';
                    case 1: return 'ONCE';
                    case 2: return 'DOCE';
                    case 3: return 'TRECE';
                    case 4: return 'CATORCE';
                    case 5: return 'QUINCE';
                    default: return 'DIECI' + Unidades(unidad);
                }
            case 2:
                switch(unidad)
                {
                    case 0: return 'VEINTE';
                    default: return 'VEINTI' + Unidades(unidad);
                }
            case 3: return DecenasY('TREINTA', unidad);
            case 4: return DecenasY('CUARENTA', unidad);
            case 5: return DecenasY('CINCUENTA', unidad);
            case 6: return DecenasY('SESENTA', unidad);
            case 7: return DecenasY('SETENTA', unidad);
            case 8: return DecenasY('OCHENTA', unidad);
            case 9: return DecenasY('NOVENTA', unidad);
            case 0: return Unidades(unidad);
        }
    }//Unidades()

    function DecenasY(strSin, numUnidades) {
        if (numUnidades > 0)
            return strSin + ' Y ' + Unidades(numUnidades)

        return strSin;
    }//DecenasY()

    function Centenas(num) {
        let centenas = Math.floor(num / 100);
        let decenas = num - (centenas * 100);

        switch(centenas)
        {
            case 1:
                if (decenas > 0)
                    return 'CIENTO ' + Decenas(decenas);
                return 'CIEN';
            case 2: return 'DOSCIENTOS ' + Decenas(decenas);
            case 3: return 'TRESCIENTOS ' + Decenas(decenas);
            case 4: return 'CUATROCIENTOS ' + Decenas(decenas);
            case 5: return 'QUINIENTOS ' + Decenas(decenas);
            case 6: return 'SEISCIENTOS ' + Decenas(decenas);
            case 7: return 'SETECIENTOS ' + Decenas(decenas);
            case 8: return 'OCHOCIENTOS ' + Decenas(decenas);
            case 9: return 'NOVECIENTOS ' + Decenas(decenas);
        }

        return Decenas(decenas);
    }//Centenas()

    function Seccion(num, divisor, strSingular, strPlural) {
        let cientos = Math.floor(num / divisor)
        let resto = num - (cientos * divisor)

        let letras = '';

        if (cientos > 0)
            if (cientos > 1)
                letras = Centenas(cientos) + ' ' + strPlural;
            else
                letras = strSingular;

        if (resto > 0)
            letras += '';

        return letras;
    }//Seccion()

    function Miles(num) {
        let divisor = 1000;
        let cientos = Math.floor(num / divisor)
        let resto = num - (cientos * divisor)

        let strMiles = Seccion(num, divisor, 'UN MIL', 'MIL');
        let strCentenas = Centenas(resto);

        if(strMiles == '')
            return strCentenas;

        return strMiles + ' ' + strCentenas;
    }//Miles()

    function Millones(num) {
        let divisor = 1000000;
        let cientos = Math.floor(num / divisor)
        let resto = num - (cientos * divisor)

        let strMillones = Seccion(num, divisor, 'UN MILLON ', 'MILLONES DE');
        let strMiles = Miles(resto);

        if(strMillones == '')
            return strMiles;

        return strMillones + ' ' + strMiles;
    }//Millones()

    return function NumeroALetras(num, currency) {
        currency = currency || {};
        let data = {
            numero: num,
            enteros: Math.floor(num),
            centavos: (((Math.round(num * 100)) - (Math.floor(num) * 100))),
            letrasCentavos: '',
            letrasMonedaPlural: currency.plural || 'PESOS CHILENOS',//'PESOS', 'Dólares', 'Bolívares', 'etcs'
            letrasMonedaSingular: currency.singular || 'PESO CHILENO', //'PESO', 'Dólar', 'Bolivar', 'etc'
            letrasMonedaCentavoPlural: currency.centPlural || 'CHIQUI PESOS CHILENOS',
            letrasMonedaCentavoSingular: currency.centSingular || 'CHIQUI PESO CHILENO'
        };

        if (data.centavos > 0) {
            data.letrasCentavos = 'CON ' + (function () {
                    if (data.centavos == 1)
                        return Millones(data.centavos) + ' ' + data.letrasMonedaCentavoSingular;
                    else
                        return Millones(data.centavos) + ' ' + data.letrasMonedaCentavoPlural;
                })();
        };

        if(data.enteros == 0)
            return 'CERO ' + data.letrasMonedaPlural + ' ' + data.letrasCentavos;
        if (data.enteros == 1)
            return Millones(data.enteros) + ' ' + data.letrasMonedaSingular + ' ' + data.letrasCentavos;
        else
            return Millones(data.enteros) + ' ' + data.letrasMonedaPlural + ' ' + data.letrasCentavos;
    };

})();