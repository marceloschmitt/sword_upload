<?php

function getInstructions() {
    $INSTRUCTIONS_TEXT = <<<TEXT
    <div style="position: relative !important; width: 100% !important; text-align: left !important;">
        <h3 style="text-align: center !important;">Módulo de integração Moodle - Repositórios</h3>
        <p style="text-align: left !important; margin-left: 5px !important;">Esse módulo permite que você envie objetos de aprendizagem para o repositório</p>
        <p style="text-align: left !important; margin-left: 5px !important;">Primeiramente você deve fornecer seu usuário e senha para se autenticar no repositório</p>
        <p style="text-align: left !important; margin-left: 5px !important;">Após, forneça as informações iniciais do objeto de aprendizagem, como título, tipo de objeto, resumo, descrição e escolha entre enviar um link ou um arquivo</p>
        <p style="text-align: left !important; margin-left: 5px !important;">na etapa seguinte, caso tenha escolhido "enviar um arquivo", selecione o arquivo em seu computador e informe a licença e os autores</p>
        <p style="text-align: left !important; margin-left: 5px !important;">Caso tenha selecionado "link", informe o link, sslecione a licença e os autores</p>
    </div>
TEXT;
    return $INSTRUCTIONS_TEXT;
}

function getLicenseTerm($license, $term) {
    $licenses = getAllLicenses();
    return $licenses[$license][$term];
}

function getAllLicenses() {
    return array(
        'unknown' => array(
            'name-moodle' => 'Other',
            'name-repositorio' => '',
            'uri' => '',
        ),
        'allrightsreserved' => array(
            'name-moodle' => 'All Rights Reserved',
            'name-repositorio' => '',
            'uri' => '',
        ),
        'public' => array(
            'name-moodle' => 'Public Domain',
            'name-repositorio' => 'CC0 1.0 Universal',
            'uri' => 'http://creativecommons.org/publicdomain/zero/1.0',
        ),
        'cc' => array(
            'name-moodle' => 'Creative Commons',
            'name-repositorio' => 'Attribution 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by/3.0/br',
        ),
        'cc-nd' => array(
            'name-moodle' => 'Creative Commons - NoDerivs',
            'name-repositorio' => 'Attribution-NoDerivs 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by-nd/3.0/br',
        ),
        'cc-nc-nd' => array(
            'name-moodle' => 'Creative Commons - No Commercial NoDerivs',
            'name-repositorio' => 'Attribution-NonCommercial-NoDerivs 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by-nc-nd/3.0/br',
        ),
        'cc-nc' => array(
            'name-moodle' => 'Creative Commons - No Commercial',
            'name-repositorio' => 'Attribution-NonCommercial 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by-nc/3.0/br',
        ),
        'cc-nc-sa' => array(
            'name-moodle' => 'Creative Commons - No Commercial ShareAlike',
            'name-repositorio' => 'Attribution-NonCommercial-ShareAlike 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/br'
        ),
        'cc-sa' => array(
            'name-moodle' => 'Creative Commons - ShareAlike',
            'name-repositorio' => 'Attribution-ShareAlike 3.0 Brazil',
            'uri' => 'http://creativecommons.org/licenses/by-sa/3.0/br',
        ),
    );
}

function licences_select_moodle() {
    $licenses = getAllLicenses();
    $select_licenses = array();
    foreach ($licenses as $key => $value) {
        $select_licenses[] = (object) array(
            'label' => $value['name-moodle'],
            'value' => $key,
        );
    }
    return $select_licenses;
}

function RetirarAcentos($frase) {
    $frase = str_replace(array("à","á","â","ã","ä","è","é","ê","ë","ì","í","î","ï","ò","ó","ô","õ","ö","ù","ú","û","ü","À","Á","Â","Ã","Ä","È","É","Ê","Ë","Ì","Í","Î","Ò","Ó","Ô","Õ","Ö","Ù","Ú","Û","Ü","ç","Ç","ñ","Ñ"),
                         array("a","a","a","a","a","e","e","e","e","i","i","i","i","o","o","o","o","o","u","u","u","u","A","A","A","A","A","E","E","E","E","I","I","I","O","O","O","O","O","U","U","U","U","c","C","n","N"), $frase);
 
    return $frase;     
}