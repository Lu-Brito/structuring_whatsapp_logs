<?php
	set_time_limit(9999999);
	$_GET["conn"] = mysqli_connect("localhost", "root", "", "whatsapp");
	
	//coloque nesse array todos os nomes que vc usou para identificar as mensagens enviadas por vc
	$_GET["meus_nomes"] = ["Lu Brito", "Lu"];
	
	function adicionar_row(&$rows, $row) {
		$msg = $row["mensagem"];
		
		//criando algumas colunas para facilitar as consultas posteriormente
		//verificando se a mensagem é um anexo
		$pos = strpos($msg, " (arquivo anexado)");
		if ($pos !== false) {
			$extensao = "";
			if (substr($msg, $pos - 4, 1) == ".") {
				$extensao = substr($msg, $pos - 3, 3);
			} elseif (substr($msg, $pos - 5, 1) == ".") {
				$extensao = substr($msg, $pos - 4, 4);
			}
			if ($extensao != "") {
				$row["extensao_anexo"] = $extensao;
				$row["is_anexo"] = 1;
			}
		}
		
		//verificando se o contato existe ou não
		if (substr($row["contato"], 0, 1) == "+"){
			$row["is_contato_desconhecido"] = 1;
		}
		
		//verificando se a mensagem foi enviada por mim
		if (in_array($row["contato"], $_GET["meus_nomes"])) {
			$row["is_enviada_por_mim"] = 1;
		}
		$rows[] = $row;
	}
	
	function escape($str) {
		return str_replace("'", "\'", $str);
	}
	
	function salvar_rows_no_banco($conversa, &$rows, $limpar_mensagens_desta_conversa = true) {
		if ($conversa == "" || count($rows) == 0) {
			return false;
		}
		if ($limpar_mensagens_desta_conversa) {
			mysqli_query($_GET["conn"], "DELETE FROM conversas WHERE conversa = '" . $conversa . "'");
		}
		
		foreach ($rows as $row) {
			$sql = "INSERT INTO conversas (conversa, data, contato, mensagem, is_contato_desconhecido, is_anexo, extensao_anexo, is_enviada_por_mim) VALUES ";
			$sql .= " (
				'" . escape($conversa) . "', 
				'" . escape($row["data"]) . "', 
				'" . escape($row["contato"]) . "', 
				'" . escape($row["mensagem"]) . "', 
				'" . (isset($row["is_contato_desconhecido"]) && $row["is_contato_desconhecido"] == 1 ? "1" : "0") . "', 
				'" . (isset($row["is_anexo"]) && $row["is_anexo"] == 1 ? "1" : "0") . "', 
				'" . (isset($row["extensao_anexo"]) ? $row["extensao_anexo"] : "") . "', 
				'" . (isset($row["is_enviada_por_mim"]) && $row["is_enviada_por_mim"] == 1 ? "1" : "0") . "')";
				mysqli_query($_GET["conn"], $sql);
		}
		
		if (strlen(mysqli_error($_GET["conn"])) == 0) {
			echo mysqli_error($_GET["conn"]) . "<br /><br />";
			return count($rows);
		} else {
			return null;
		}
	}
	
	if ($_POST){
		$file = $_FILES["arquivo"];
		if ($file["name"] == ""){
			echo "Nenhum arquivo selecionado";
		} else {
			$conversa = $file["name"];
			$handle = fopen($file["tmp_name"], "r");
			if ($handle) {
				$data = "";
				$contato = "";
				$mensagem = "";
				$rows = [];
				while (($line = fgets($handle)) !== false) {
					if (strpos($line, "As mensagens e as chamadas são protegidas com a criptografia de ponta a ponta e ficam somente entre você e os participantes desta conversa. Nem mesmo o WhatsApp pode ler ou ouvi-las. Toque para saber mais.") !== false){
						//ignorando quando for aquela mensagem padrão do whatsapp
						continue;
					}
					
					$is_nova_mensagem = false;
					//identificando quando é uma linha de uma nova mensagem ou quando é uma nova linha da mensagem anterior
					if (substr($line, 2, 1) == "/" && substr($line, 5, 1) == "/" && substr($line, 10, 1) == " " && substr($line, 13, 1) == ":" && substr($line, 17, 1) == "-"){
						//$mensagem = "";
						$pedaco = substr($line, 19); //trecho da linha após a data
						if (strpos($pedaco, ": ") === false){
							//caso a mensagem não possua ": " significa que não é uma mensagem (quando adiciona alguém no grupo, por exemplo, e por isso deve ser ignorada)
							continue;
						}
						if ($data != "") {
							adicionar_row($rows, ["data" => $data, "contato" => $contato, "mensagem" => $mensagem]);
							//$rows[] = ;
						}
						$data = substr($line, 6, 4) . "-" . substr($line, 3, 2) . "-" . substr($line, 0, 2) . " " . substr($line, 11, 5) . ":00"; //convertendo a data para o formato do banco de dados
						$contato = substr($pedaco, 0, strpos($pedaco, ": "));
						$mensagem = substr($pedaco, strpos($pedaco, ": ") + 2);
						$is_nova_mensagem = true;
					}
					if (!$is_nova_mensagem) {
						$mensagem .= $line;
					}
				}
				//gravando a última mensagem que acaba ficando fora do loop
				adicionar_row($rows, ["data" => $data, "contato" => $contato, "mensagem" => $mensagem]);
				
				$registros = salvar_rows_no_banco($conversa, $rows);
				if ($registros === null) {
					"Não foi possível inserir os registros";
				} else {
					echo $registros . " conversa(s) inserida(s) com sucesso!";
				}
			} else {
				echo "Erro ao tentar ler o arquivo";
			} 
		}
	}
?>


<br /><br />
<form method="post" enctype="multipart/form-data">
	Selecione o arquivo de log:
	<input type="hidden" name="whatever" value="1" />
	<input type="file" name="arquivo" accept=".txt" />
	<br />
	<br />
	<input type="submit" value="vai" />
</form>