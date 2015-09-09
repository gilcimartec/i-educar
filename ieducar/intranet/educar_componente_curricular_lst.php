<?php
//error_reporting(E_ERROR);
//ini_set("display_errors", 1);
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	*																	     *
	*	@author Prefeitura Municipal de Itaja�								 *
	*	@updated 29/03/2007													 *
	*   Pacote: i-PLB Software P�blico Livre e Brasileiro					 *
	*																		 *
	*	Copyright (C) 2006	PMI - Prefeitura Municipal de Itaja�			 *
	*						ctima@itajai.sc.gov.br					    	 *
	*																		 *
	*	Este  programa  �  software livre, voc� pode redistribu�-lo e/ou	 *
	*	modific�-lo sob os termos da Licen�a P�blica Geral GNU, conforme	 *
	*	publicada pela Free  Software  Foundation,  tanto  a vers�o 2 da	 *
	*	Licen�a   como  (a  seu  crit�rio)  qualquer  vers�o  mais  nova.	 *
	*																		 *
	*	Este programa  � distribu�do na expectativa de ser �til, mas SEM	 *
	*	QUALQUER GARANTIA. Sem mesmo a garantia impl�cita de COMERCIALI-	 *
	*	ZA��O  ou  de ADEQUA��O A QUALQUER PROP�SITO EM PARTICULAR. Con-	 *
	*	sulte  a  Licen�a  P�blica  Geral  GNU para obter mais detalhes.	 *
	*																		 *
	*	Voc�  deve  ter  recebido uma c�pia da Licen�a P�blica Geral GNU	 *
	*	junto  com  este  programa. Se n�o, escreva para a Free Software	 *
	*	Foundation,  Inc.,  59  Temple  Place,  Suite  330,  Boston,  MA	 *
	*	02111-1307, USA.													 *
	*																		 *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
require_once ("include/clsBase.inc.php");
require_once ("include/clsListagem.inc.php");
require_once ("include/clsBanco.inc.php");
require_once( "include/modules/clsModulesComponenteCurricular.inc.php" );
require_once( "modules/ComponenteCurricular/Model/TipoBase.php" );

class clsIndexBase extends clsBase
{
	function Formular()
	{
		$this->SetTitulo( "{$this->_instituicao} i-Educar - Componentes curriculares" );
		$this->processoAp = "946";
		$this->addEstilo("localizacaoSistema");
	}
}

class indice extends clsListagem
{
	/**
	 * Referencia pega da session para o idpes do usuario atual
	 *
	 * @var int
	 */
	var $pessoa_logada;

	/**
	 * Titulo no topo da pagina
	 *
	 * @var int
	 */
	var $titulo;

	/**
	 * Quantidade de registros a ser apresentada em cada pagina
	 *
	 * @var int
	 */
	var $limite;

	/**
	 * Inicio dos registros a serem exibidos (limit)
	 *
	 * @var int
	 */
	var $offset;

  var $ref_cod_instituicao;
  var $nome;
  var $abreviatura;
  var $tipo_base;
	var $area_conhecimento_id;

	function Gerar()
	{
		@session_start();
		$this->pessoa_logada = $_SESSION['id_pessoa'];
		session_write_close();

		$this->titulo = "Componentes curriculares - Listagem";

		foreach( $_GET AS $var => $val ) // passa todos os valores obtidos no GET para atributos do objeto
			$this->$var = ( $val === "" ) ? null: $val;



		$lista_busca = array(
			"Nome",
			"Abreviatura",
      "Base",
      "&Aacute;rea de conhecimento"
		);

		$obj_permissoes = new clsPermissoes();
		$nivel_usuario = $obj_permissoes->nivel_acesso($this->pessoa_logada);
		if ($nivel_usuario == 1)
			$lista_busca[] = "Institui&ccedil;&atilde;o";

		$this->addCabecalhos($lista_busca);

		include("include/pmieducar/educar_campo_lista.php");

		// outros Filtros
    $this->campoTexto( "nome", "Nome", $this->nome, 30, 255, false );
		$this->campoTexto( "abreviatura", "Abreviatura", $this->abreviatura, 30, 255, false );

    $tipos = ComponenteCurricular_Model_TipoBase::getInstance();
    $tipos = $tipos->getEnums();
    $tipos = Portabilis_Array_Utils::insertIn(null, 'Selecionar', $tipos);

    $options = array(
      'label'       => 'Base Curricular',
      'placeholder' => 'Base curricular',
      'value'       => $this->tipo_base,
      'resources'   => $tipos
    );

    $this->inputsHelper()->select('tipo_base', $options);


		// Paginador
		$this->limite = 20;
		$this->offset = ( $_GET["pagina_{$this->nome}"] ) ? $_GET["pagina_{$this->nome}"]*$this->limite-$this->limite: 0;

		$objCC = new clsModulesComponenteCurricular();
		$objCC->setOrderby( "cc.nome ASC" );
		$objCC->setLimite( $this->limite, $this->offset );

		$lista = $objCC->lista(
        $this->ref_cod_instituicao,
        $this->nome,
        $this->abreaviatura,
        $this->tipo_base
      );

		$total = $objCC->_total;

		// monta a lista
		if( is_array( $lista ) && count( $lista ) )
		{
			foreach ( $lista AS $registro )
			{
				if( class_exists( "clsPmieducarInstituicao" ) )
				{
					$obj_cod_instituicao = new clsPmieducarInstituicao( $registro["instituicao_id"] );
					$obj_cod_instituicao_det = $obj_cod_instituicao->detalhe();
					$registro["instituicao_id"] = $obj_cod_instituicao_det["nm_instituicao"];
				}
				else
				{
					$registro["instituicao_id"] = "Erro na gera&ccedil;&atilde;o";
					echo "<!--\nErro\nClasse n&atilde;o existente: clsPmieducarInstituicao\n-->";
				}
				$lista_busca = array(
          "<a href=\"/module/ComponenteCurricular/view?id={$registro["id"]}\">{$registro["nome"]}</a>",
          "<a href=\"/module/ComponenteCurricular/view?id={$registro["id"]}\">{$registro["abreviatura"]}</a>",
					"<a href=\"/module/ComponenteCurricular/view?id={$registro["id"]}\">".$tipos[$registro["tipo_base"]]."</a>",
					"<a href=\"/module/ComponenteCurricular/view?id={$registro["id"]}\">{$registro["area_conhecimento"]}</a>"
				);

				if ($nivel_usuario == 1)
					$lista_busca[] = "<a href=\"module/ComponenteCurricular/view?id={$registro["id"]}\">{$registro["instituicao_id"]}</a>";
				$this->addLinhas($lista_busca);
			}
		}
		$this->addPaginador2( "educar_componente_curricular_lst.php", $total, $_GET, $this->nome, $this->limite );

		if( $obj_permissoes->permissao_cadastra( 580, $this->pessoa_logada,3 ) )
		{
			$this->acao = "go(\"/module/ComponenteCurricular/edit\")";
			$this->nome_acao = "Novo";
		}
		$this->largura = "100%";

	    $localizacao = new LocalizacaoSistema();
	    $localizacao->entradaCaminhos( array(
	         $_SERVER['SERVER_NAME']."/intranet" => "In&iacute;cio",
	         "educar_index.php"                  => "i-Educar - Escola",
	         ""                                  => "Listagem de componentes curriculares"
	    ));
	    $this->enviaLocalizacao($localizacao->montar());
	}

}
// cria uma extensao da classe base
$pagina = new clsIndexBase();
// cria o conteudo
$miolo = new indice();
// adiciona o conteudo na clsBase
$pagina->addForm( $miolo );
// gera o html
$pagina->MakeAll();
?>