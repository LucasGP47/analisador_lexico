<?php

$operadores = array('(', ')', '[', ']', '{', '}', '.', ';', '!', '*', '+', '-', '/', '%', '=', '==', '&');

$delta = array(
    'q0' => array_merge(
        array_combine(range('A', 'Z'), array_fill(0, 26, 'q1')),
        array_combine(range('a', 'z'), array_fill(0, 26, 'q1')),
        array_combine(range('0', '9'), array_fill(0, 10, 'q3')),
        array_fill_keys($operadores, 'q2'), 
        [' ' => 'q0', "\n" => 'q0', "\t" => 'q0']
    ),
    'q1' => array_merge(
        array_combine(range('A', 'Z'), array_fill(0, 26, 'q1')),
        array_combine(range('a', 'z'), array_fill(0, 26, 'q1')),
        array_combine(range('0', '9'), array_fill(0, 10, 'q1')),
        array_fill_keys($operadores, 'q2'), 
        [' ' => 'q0', "\n" => 'q0', "\t" => 'q0']
    ),
    'q2' => [' ' => 'q0', "\n" => 'q0', "\t" => 'q0'],
    'q3' => array_merge(
        array_combine(range('0', '9'), array_fill(0, 10, 'q3')),
        array_fill_keys($operadores, 'q2'),
        [' ' => 'q0', "\n" => 'q0', "\t" => 'q0']
    )
);

$Q = array('q0', 'q1', 'q2', 'q3');

function analisar_codigo($codigo) {
    global $delta, $operadores;

    $palavrasReservadas = array("SE", "SENAO", "FACA", "ENQUANTO", "IMPRIMA", "VAR", "LEIA", "ESCREVA");

    $estado = 'q0';  
    $tokens = array();  
    $erros = array();  
    $buffer = '';  
    $linha = 1;  
    $posicao = 1;  
    
    for ($i = 0; $i < strlen($codigo); $i++) {
        $char = $codigo[$i];
    
        if ($char === ' ' || $char === "\n" || $char === "\t") {
            if ($char === "\n") {
                $linha++;
                $posicao = 1;
            }
            if (!empty($buffer) && estado_final($estado)) {
                processar_token($buffer, $tokens, $linha, $posicao, $palavrasReservadas);
                $buffer = ''; 
                $estado = 'q0';  
            }
            continue;
        }

        if (isset($delta[$estado][$char])) {
            $buffer .= $char;  
            $estado = $delta[$estado][$char];  
        } else {
            if (estado_final($estado)) {
                processar_token($buffer, $tokens, $linha, $posicao, $palavrasReservadas);
                $buffer = ''; 
                $estado = 'q0';  
                $i--; 
            } else {
                $erros[] = "Erro: caractere inválido '$char' na linha $linha, posição $posicao.";
                $buffer = ''; 
                $estado = 'q0'; 
            }
        }
    
        $posicao++;
    }
    
    if (!empty($buffer)) {
        if (estado_final($estado)) {
            processar_token($buffer, $tokens, $linha, $posicao, $palavrasReservadas);
        } else {
            $erros[] = "Erro: token inválido '$buffer' na linha $linha, posição $posicao.";
        }
    }
    
    return array('tokens' => $tokens, 'erros' => $erros);
}

function processar_token($buffer, &$tokens, $linha, $posicao, $palavrasReservadas) {
    global $operadores; 

    if (in_array(strtoupper($buffer), $palavrasReservadas)) {
        $tokens[] = array('token' => $buffer, 'tipo' => 'Palavra Reservada', 'linha' => $linha, 'posicao' => $posicao);
    } elseif (is_numeric($buffer)) {
        $tokens[] = array('token' => $buffer, 'tipo' => 'Constante', 'linha' => $linha, 'posicao' => $posicao);
    } elseif (in_array($buffer, $operadores)) {
        $tokens[] = array('token' => $buffer, 'tipo' => 'Operador', 'linha' => $linha, 'posicao' => $posicao);
    } else {
        $tokens[] = array('token' => $buffer, 'tipo' => 'Identificador', 'linha' => $linha, 'posicao' => $posicao);
    }
}

function estado_final($estado) {
    $estados_finais = array('q1', 'q3', 'q2'); 
    return in_array($estado, $estados_finais);
}

if (isset($_POST['codigo'])) { 
    $codigo = $_POST['codigo'];
    $resultado = analisar_codigo($codigo);

    if (!empty($resultado['tokens'])) {
        echo "Tokens:<br>";
        foreach ($resultado['tokens'] as $token) {
            echo "Token: {$token['token']} (Tipo: {$token['tipo']}, Linha: {$token['linha']}, Posição: {$token['posicao']})<br>";
        }
    } else {
        echo "Nenhum token identificado.<br>";
    }

    if (!empty($resultado['erros'])) {
        echo "Erros:<br>";
        foreach ($resultado['erros'] as $erro) {
            echo "$erro<br>";
        }
    } else {
        echo "Nenhum erro.<br>";
    }
}
?>
