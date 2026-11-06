<?php
/*
Plugin Name: OLX Smart Search & Heuristic Category Detection
Plugin URI: https://github.com/osclass/
Description: Ultra-fast AJAX autocomplete, instant listing preview, and intelligent category detection for Osclass.
Version: 1.3.0
Author: Advanced Agentic Engineer
Author URI: https://osclass.org/
Short Name: olx_smart_search
Plugin update URI: 
*/

function olx_smart_search_install() {}
function olx_smart_search_uninstall() {}

// Helper: Remove accents and lowercase
function olx_smart_remove_accents($str) {
    $map = array(
        'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','ç'=>'c',
        'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e',
        'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i',
        'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o',
        'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u',
        'À'=>'a','Á'=>'a','Â'=>'a','Ã'=>'a','Ä'=>'a','Ç'=>'c',
        'È'=>'e','É'=>'e','Ê'=>'e','Ë'=>'e',
        'Ì'=>'i','Í'=>'i','Î'=>'i','Ï'=>'i',
        'Ò'=>'o','Ó'=>'o','Ô'=>'o','Õ'=>'o','Ö'=>'o',
        'Ù'=>'u','Ú'=>'u','Û'=>'u','Ü'=>'u'
    );
    return strtolower(strtr($str, $map));
}

// Helper: Get category ID and all its active children IDs
function olx_smart_get_category_and_children($comm, $cat_id) {
    $ids = array((int)$cat_id);
    $res = $comm->query(sprintf("SELECT pk_i_id FROM %st_category WHERE fk_i_parent_id = %d AND b_enabled = 1", DB_TABLE_PREFIX, (int)$cat_id));
    if ($res) {
        $rows = $res->result();
        foreach ($rows as $r) {
            $ids[] = (int)$r['pk_i_id'];
        }
    }
    return $ids;
}

// Global Broad Category Tokens Map
// Global Synonyms Map
function olx_smart_get_synonyms() {
    return array(
        'vw' => 'volkswagen',
        'volkswagen' => 'vw',
        'gm' => 'chevrolet',
        'chevrolet' => 'gm',
        'mercedes' => 'mercedes-benz',
        'apto' => 'apartamento',
        'bike' => 'bicicleta',
        'noteb' => 'notebook',
        'cel' => 'celular',
        'celu' => 'celular',
        'tv' => 'televisao',
        'comput' => 'computador',
        'bicicl' => 'bicicleta'
    );
}

function olx_smart_get_broad_tokens() {
    return array(
        'carro' => 31, 'carros' => 31, 'carr' => 31, 'automovel' => 31, 'automoveis' => 31, 'veiculo' => 2, 'veiculos' => 2, 'veic' => 2, 'auto' => 2, 'autos' => 2,
        'moto' => 33, 'motos' => 33, 'mot' => 33, 'motocicleta' => 33, 'motocicletas' => 33, 'scooter' => 33, 'scooters' => 33,
        'caminhao' => 36, 'caminhoes' => 36, 'caminh' => 36, 'carreta' => 36, 'carretas' => 36, 'truck' => 36,
        'barco' => 34, 'barcos' => 34, 'lancha' => 34, 'lanchas' => 34, 'jetski' => 34,
        'peca' => 32, 'pecas' => 32, 'pneu' => 32, 'pneus' => 32, 'roda' => 32, 'rodas' => 32,
        'onibus' => 35, 'microonibus' => 35, 'van' => 35, 'vans' => 35,
        'imovel' => 4, 'imoveis' => 4, 'imov' => 4, 'apartamento' => 4, 'apartamentos' => 4, 'apto' => 4, 'aptos' => 4, 'apart' => 4,
        'casa' => 4, 'casas' => 4, 'aluguel' => 44, 'alugar' => 44, 'temporada' => 47, 'praia' => 47,
        'terreno' => 49, 'terrenos' => 49, 'lote' => 49, 'lotes' => 49, 'sitio' => 49, 'sitios' => 49, 'fazenda' => 49, 'chacara' => 49,
        'quarto' => 45, 'quartos' => 45, 'republica' => 45,
        'celular' => 15, 'celulares' => 15, 'cel' => 15, 'celu' => 15, 'smartphone' => 15, 'smartphones' => 15,
        'computador' => 17, 'computadores' => 17, 'comput' => 17, 'notebook' => 17, 'notebooks' => 17, 'noteb' => 17, 'laptop' => 17, 'laptops' => 17, 'pc' => 17,
        'videogame' => 29, 'videogames' => 29, 'console' => 29, 'consoles' => 29, 'games' => 29, 'jogos' => 29,
        'tv' => 19, 'tvs' => 19, 'smart tv' => 19, 'televisao' => 19, 'televisoes' => 19,
        'sofa' => 23, 'sofas' => 23, 'cama' => 23, 'camas' => 23, 'colchao' => 23, 'colchoes' => 23,
        'mesa' => 23, 'mesas' => 23, 'cadeira' => 23, 'cadeiras' => 23, 'armario' => 23, 'armarios' => 23,
        'movel' => 23, 'moveis' => 23, 'geladeira' => 97, 'geladeiras' => 97, 'fogao' => 97, 'fogoes' => 97,
        'roupa' => 16, 'roupas' => 16, 'vestido' => 16, 'vestidos' => 16, 'tenis' => 16, 'sapato' => 16, 'calcado' => 16, 'calcados' => 16,
        'bolsa' => 24, 'bolsas' => 24, 'mochila' => 24, 'relogio' => 24, 'relogios' => 24, 'joia' => 24, 'joias' => 24,
        'beleza' => 22, 'perfume' => 22, 'perfumes' => 22, 'maquiagem' => 22, 'cosmetico' => 22, 'cosmeticos' => 22,
        'bicicleta' => 26, 'bicicletas' => 26, 'bike' => 26, 'bikes' => 26, 'bicicl' => 26,
        'livro' => 12, 'livros' => 12, 'revista' => 12, 'revistas' => 12, 'hq' => 12, 'hqs' => 12,
        'pet' => 111, 'pets' => 111, 'cachorro' => 111, 'cachorros' => 111, 'cao' => 111, 'caes' => 111, 'gato' => 111, 'gatos' => 111, 'racao' => 9,
        'trator' => 115, 'tratores' => 115, 'agricola' => 115,
        'servico' => 5, 'servicos' => 5, 'reforma' => 60, 'reformas' => 60, 'frete' => 59, 'fretes' => 59, 'mudanca' => 59,
        'emprego' => 8, 'empregos' => 8, 'vaga' => 8, 'vagas' => 8, 'trabalho' => 8
    );
}

// -------------------------------------------------------------
// 1. ULTRA-FAST AJAX ENDPOINT (Intercepts on 'init' hook)
// -------------------------------------------------------------
function olx_smart_search_ajax_handler() {
    if (Params::getParam('page') !== 'smart_search_ajax') {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: private, max-age=30');

    $q = trim((string)Params::getParam('q'));
    if (mb_strlen($q, 'UTF-8') < 2) {
        echo json_encode(array('status' => 'empty', 'results' => array(), 'category' => null));
        exit();
    }

    $conn = DBConnectionClass::newInstance();
    $db = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $normalized_q = olx_smart_remove_accents($q);

    // A. HEURISTIC CATEGORY DETECTION
    $detected_category = olx_smart_detect_category($comm, $q, $normalized_q);

    // B. FETCH LISTINGS (Two-phase indexed query)
    $items = olx_smart_fetch_items($comm, $q, $detected_category);

    echo json_encode(array(
        'status' => 'ok',
        'query' => $q,
        'category' => $detected_category,
        'items' => $items,
        'count' => count($items)
    ));
    exit();
}
osc_add_hook('init', 'olx_smart_search_ajax_handler', 1);


// -------------------------------------------------------------
// 2. SEARCH ENGINE HOOK: DYNAMIC PATTERN EXPANSION
// -------------------------------------------------------------
function olx_smart_search_cond_pattern($cond, $pattern) {
    if (empty($pattern)) return $cond;

    $conn = DBConnectionClass::newInstance();
    $db = $conn->getOsclassDb();
    $comm = new DBCommandClass($db);

    $norm = olx_smart_remove_accents(trim($pattern));
    $words = explode(' ', $norm);
    $broad_tokens = olx_smart_get_broad_tokens();
    $synonyms = olx_smart_get_synonyms();

    $cat_id = null;
    $residual_words = array();

    foreach ($words as $w) {
        $w = trim($w);
        if ($w === '') continue;
        if (isset($broad_tokens[$w])) {
            $cat_id = $broad_tokens[$w];
        } else {
            if (mb_strlen($w, 'UTF-8') >= 3) {
                foreach ($broad_tokens as $bt => $cid) {
                    if (strpos($bt, $w) === 0) {
                        $cat_id = $cid;
                        break;
                    }
                }
            }
            if ($cat_id === null) {
                $residual_words[] = $w;
            }
        }
    }

    $residual = trim(implode(' ', $residual_words));

    // Case 1: Broad department/category matched
    if ($cat_id !== null) {
        $cat_ids = olx_smart_get_category_and_children($comm, $cat_id);
        $cat_in = implode(',', array_map('intval', $cat_ids));

        if ($residual === '') {
            return sprintf("(d.s_title LIKE %s OR d.fk_i_item_id IN (SELECT pk_i_id FROM %st_item WHERE fk_i_category_id IN (%s)))",
                $comm->escape('%' . $norm . '%'), DB_TABLE_PREFIX, $cat_in);
        } else {
            // Match category AND residual words (e.g. 'carro flex')
            $r_words = explode(' ', $residual);
            $r_conds = array();
            foreach ($r_words as $rw) {
                $rw = trim($rw);
                if ($rw === '') continue;
                $r_conds[] = sprintf("lower(concat(d.s_title, ' ', d.s_description)) LIKE %s", $comm->escape('%' . $rw . '%'));
            }
            $r_where = !empty($r_conds) ? implode(' AND ', $r_conds) : "1=1";
            return sprintf("(%s AND d.fk_i_item_id IN (SELECT pk_i_id FROM %st_item WHERE fk_i_category_id IN (%s)))",
                $r_where, DB_TABLE_PREFIX, $cat_in);
        }
    }

    // Case 2: Multi-word query with synonyms (e.g. 'corolla branco', 'vw polo', 'tesla 2021')
    $clean_words = array();
    foreach ($words as $w) {
        $w = trim($w);
        if ($w !== '') $clean_words[] = $w;
    }

    if (!empty($clean_words)) {
        $word_conds = array();
        foreach ($clean_words as $w) {
            $sub = array();
            $sub[] = sprintf("lower(concat(d.s_title, ' ', d.s_description)) LIKE %s", $comm->escape('%' . $w . '%'));
            if (isset($synonyms[$w])) {
                $sub[] = sprintf("lower(concat(d.s_title, ' ', d.s_description)) LIKE %s", $comm->escape('%' . $synonyms[$w] . '%'));
            }
            $word_conds[] = '(' . implode(' OR ', $sub) . ')';
        }
        return implode(' AND ', $word_conds);
    }

    return sprintf("lower(concat(d.s_title, ' ', d.s_description)) LIKE %s",
        $comm->escape('%' . $norm . '%'));
}
osc_add_filter('search_cond_pattern', 'olx_smart_search_cond_pattern');


// -------------------------------------------------------------
// 3. HEURISTIC CATEGORY MATCHER
// -------------------------------------------------------------
function olx_smart_detect_category($comm, $raw_q, $norm_q) {
    $broad_tokens = olx_smart_get_broad_tokens();

    $brand_models = array(
        'honda' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'civic' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'city' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'fit' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'toyota' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'corolla' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'hilux' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'yaris' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'fiat' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'palio' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'uno' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'strada' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'toro' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'volkswagen' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'vw' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'gol' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'polo' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        't-cross' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'nivus' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'chevrolet' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'onix' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'tracker' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        's10' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'hyundai' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'hb20' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'creta' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'jeep' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'renegade' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'compass' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'gwm' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'haval' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'ora' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'chery' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'tiggo' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'suzuki' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'jimny' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'jaguar' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'audi' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'bmw' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'byd' => array('id' => 31, 'name' => 'Carros, vans e utilitários', 'icon' => 'fas fa-car'),
        'yamaha' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'fazer' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'titan' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'biz' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'fan' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'xre' => array('id' => 33, 'name' => 'Motos', 'icon' => 'fas fa-motorcycle'),
        'iphone' => array('id' => 15, 'name' => 'Celulares e telefonia', 'icon' => 'fas fa-mobile-alt'),
        'galaxy' => array('id' => 15, 'name' => 'Celulares e telefonia', 'icon' => 'fas fa-mobile-alt'),
        'redmi' => array('id' => 15, 'name' => 'Celulares e telefonia', 'icon' => 'fas fa-mobile-alt'),
        'macbook' => array('id' => 17, 'name' => 'Computadores e informática', 'icon' => 'fas fa-laptop'),
        'ps5' => array('id' => 29, 'name' => 'Videogames e consoles', 'icon' => 'fas fa-gamepad'),
        'ps4' => array('id' => 29, 'name' => 'Videogames e consoles', 'icon' => 'fas fa-gamepad'),
        'xbox' => array('id' => 29, 'name' => 'Videogames e consoles', 'icon' => 'fas fa-gamepad'),
        'nintendo' => array('id' => 29, 'name' => 'Videogames e consoles', 'icon' => 'fas fa-gamepad'),
        'jacone' => array('id' => 47, 'name' => 'Aluguel por temporada', 'icon' => 'fas fa-umbrella-beach')
    );

    $words = explode(' ', $norm_q);

    // 1. Broad tokens exact or stem match
    foreach ($words as $w) {
        $w = trim($w);
        if ($w === '') continue;
        $matched_cid = null;
        if (isset($broad_tokens[$w])) {
            $matched_cid = $broad_tokens[$w];
        } else if (mb_strlen($w, 'UTF-8') >= 3) {
            foreach ($broad_tokens as $bt => $cid) {
                if (strpos($bt, $w) === 0) {
                    $matched_cid = $cid;
                    break;
                }
            }
        }
        if ($matched_cid !== null) {
            $cat_info = osc_get_category_row($matched_cid);
            $cat_desc = Category::newInstance()->findByPrimaryKey($matched_cid);
            return array(
                'id' => (int)$matched_cid,
                'name' => isset($cat_desc['s_name']) ? $cat_desc['s_name'] : 'Categoria',
                'icon' => !empty($cat_info['s_icon']) ? $cat_info['s_icon'] : 'fas fa-tag',
                'broad' => true
            );
        }
    }

    // 2. Specific Brand / Model tokens
    foreach ($words as $w) {
        $w = trim($w);
        if (isset($brand_models[$w])) {
            $cat = $brand_models[$w];
            $cat['broad'] = false;
            return $cat;
        }
    }

    // 3. Category description table lookup (ranked by items count)
    $sql = sprintf("SELECT c.pk_i_id, cd.s_name, c.s_icon, COALESCE(cs.i_num_items, 0) as num_items 
        FROM %st_category c 
        JOIN %st_category_description cd ON c.pk_i_id = cd.fk_i_category_id AND cd.fk_c_locale_code = %s 
        LEFT JOIN %st_category_stats cs ON c.pk_i_id = cs.fk_i_category_id 
        WHERE c.b_enabled = 1 AND cd.s_name LIKE %s 
        ORDER BY (cd.s_name LIKE %s) DESC, num_items DESC, (c.fk_i_parent_id IS NULL) DESC LIMIT 1",
        DB_TABLE_PREFIX, DB_TABLE_PREFIX, $comm->escape('pt_BR'), DB_TABLE_PREFIX,
        $comm->escape('%' . $raw_q . '%'), $comm->escape($raw_q . '%'));
    $res = $comm->query($sql);
    if ($res && $row = $res->row()) {
        return array(
            'id' => (int)$row['pk_i_id'],
            'name' => $row['s_name'],
            'icon' => $row['s_icon'] ? $row['s_icon'] : 'fas fa-tag',
            'broad' => true
        );
    }

    // 4. Dynamic query from existing items
    $sql_dyn = sprintf("SELECT i.fk_i_category_id, cd.s_name, c.s_icon, COUNT(*) as cnt
        FROM %st_item i
        JOIN %st_item_description d ON i.pk_i_id = d.fk_i_item_id
        JOIN %st_category c ON i.fk_i_category_id = c.pk_i_id
        JOIN %st_category_description cd ON c.pk_i_id = cd.fk_i_category_id AND cd.fk_c_locale_code = %s
        WHERE i.b_active = 1 AND i.b_enabled = 1 AND i.b_spam = 0 AND d.s_title LIKE %s
        GROUP BY i.fk_i_category_id ORDER BY cnt DESC LIMIT 1",
        DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX,
        $comm->escape('pt_BR'), $comm->escape('%' . $raw_q . '%'));
    $res_dyn = $comm->query($sql_dyn);
    if ($res_dyn && $row_dyn = $res_dyn->row()) {
        return array(
            'id' => (int)$row_dyn['fk_i_category_id'],
            'name' => $row_dyn['s_name'],
            'icon' => $row_dyn['s_icon'] ? $row_dyn['s_icon'] : 'fas fa-tag',
            'broad' => false
        );
    }

    return null;
}


// -------------------------------------------------------------
// 4. TWO-PHASE ULTRA-FAST LISTING FETCH
// -------------------------------------------------------------
function olx_smart_fetch_items($comm, $q, $detected_category = null) {
    $norm = olx_smart_remove_accents(trim($q));
    $words = explode(' ', $norm);
    $broad_tokens = olx_smart_get_broad_tokens();
    $synonyms = olx_smart_get_synonyms();

    $residual_words = array();
    foreach ($words as $w) {
        $w = trim($w);
        if ($w === '') continue;
        if (!isset($broad_tokens[$w])) {
            $residual_words[] = $w;
        }
    }

    $active_words = !empty($residual_words) ? $residual_words : $words;

    // Build multi-word title conditions
    $title_conds = array();
    foreach ($active_words as $w) {
        $w = trim($w);
        if ($w === '') continue;
        $sub = array();
        $sub[] = sprintf("d.s_title LIKE %s", $comm->escape('%' . $w . '%'));
        if (isset($synonyms[$w])) {
            $sub[] = sprintf("d.s_title LIKE %s", $comm->escape('%' . $synonyms[$w] . '%'));
        }
        $title_conds[] = '(' . implode(' OR ', $sub) . ')';
    }
    $where_title = !empty($title_conds) ? implode(' AND ', $title_conds) : "1=1";

    // 1. Direct title matching
    $sql = sprintf("SELECT 
            i.pk_i_id, 
            d.s_title, 
            i.i_price, 
            i.fk_c_currency_code,
            c.pk_i_id AS cat_id,
            cd.s_name AS cat_name
        FROM %st_item i
        JOIN %st_item_description d ON i.pk_i_id = d.fk_i_item_id
        JOIN %st_category c ON i.fk_i_category_id = c.pk_i_id
        JOIN %st_category_description cd ON c.pk_i_id = cd.fk_i_category_id AND cd.fk_c_locale_code = %s
        WHERE i.b_active = 1 AND i.b_enabled = 1 AND i.b_spam = 0
          AND (%s)
        ORDER BY i.dt_pub_date DESC
        LIMIT 4",
        DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX,
        $comm->escape('pt_BR'), $where_title);

    $res = $comm->query($sql);
    $rows = $res ? $res->result() : array();

    // 2. Supplement listings if fewer than 4 items and detected category exists
    if (count($rows) < 4 && $detected_category && !empty($detected_category['id'])) {
        $cat_ids = olx_smart_get_category_and_children($comm, $detected_category['id']);
        if (!empty($cat_ids)) {
            $cat_in = implode(',', array_map('intval', $cat_ids));
            $exclude_ids = array();
            foreach ($rows as $r) {
                $exclude_ids[] = (int)$r['pk_i_id'];
            }
            $exclude_sql = !empty($exclude_ids) ? sprintf("AND i.pk_i_id NOT IN (%s)", implode(',', $exclude_ids)) : "";
            $needed = 4 - count($rows);

            $sql_sup = sprintf("SELECT 
                    i.pk_i_id, 
                    d.s_title, 
                    i.i_price, 
                    i.fk_c_currency_code,
                    c.pk_i_id AS cat_id,
                    cd.s_name AS cat_name
                FROM %st_item i
                JOIN %st_item_description d ON i.pk_i_id = d.fk_i_item_id
                JOIN %st_category c ON i.fk_i_category_id = c.pk_i_id
                JOIN %st_category_description cd ON c.pk_i_id = cd.fk_i_category_id AND cd.fk_c_locale_code = %s
                WHERE i.b_active = 1 AND i.b_enabled = 1 AND i.b_spam = 0
                  AND i.fk_i_category_id IN (%s) %s
                ORDER BY i.dt_pub_date DESC
                LIMIT %d",
                DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX, DB_TABLE_PREFIX,
                $comm->escape('pt_BR'), $cat_in, $exclude_sql, $needed);
            $res_sup = $comm->query($sql_sup);
            if ($res_sup) {
                $extra = $res_sup->result();
                $rows = array_merge($rows, $extra);
            }
        }
    }

    if (empty($rows)) {
        return array();
    }

    // 3. Batch Image Fetch
    $item_ids = array();
    foreach ($rows as $r) {
        $item_ids[] = (int)$r['pk_i_id'];
    }

    $images = array();
    $sql_img = sprintf("SELECT fk_i_item_id, pk_i_id, s_path, s_extension 
        FROM %st_item_resource 
        WHERE fk_i_item_id IN (%s)
        GROUP BY fk_i_item_id",
        DB_TABLE_PREFIX, implode(',', $item_ids));
    $res_img = $comm->query($sql_img);
    if ($res_img) {
        $img_rows = $res_img->result();
        foreach ($img_rows as $ir) {
            $images[$ir['fk_i_item_id']] = osc_apply_filter('resource_path', osc_base_url() . $ir['s_path']) . $ir['pk_i_id'] . '_thumbnail.' . $ir['s_extension'];
        }
    }

    // 4. Format Results
    $output = array();
    foreach ($rows as $row) {
        $img_url = isset($images[$row['pk_i_id']]) ? $images[$row['pk_i_id']] : null;
        $formatted_price = ($row['i_price'] !== null && $row['i_price'] > 0) 
            ? 'R$ ' . number_format($row['i_price'] / 1000000, 0, ',', '.') 
            : 'Sob consulta';

        $output[] = array(
            'id' => (int)$row['pk_i_id'],
            'title' => $row['s_title'],
            'price' => $formatted_price,
            'category' => $row['cat_name'],
            'img' => $img_url,
            'url' => osc_item_url_ns($row['pk_i_id'])
        );
    }

    return $output;
}


// -------------------------------------------------------------
// 5. INJECT CSS IN HEADER
// -------------------------------------------------------------
function olx_smart_search_header() {
    echo '<style>
    .olx-smart-wrapper { position: relative; width: 100%; }
    .olx-smart-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        min-width: 380px;
        max-width: 580px;
        width: 100%;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.18), 0 2px 6px rgba(15, 23, 42, 0.08);
        border: 1px solid #e2e8f0;
        z-index: 99999;
        overflow: hidden;
        display: none;
        animation: olxDropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
        font-family: inherit;
    }
    @keyframes olxDropdownFadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .olx-smart-section { padding: 8px 0; }
    .olx-smart-section:not(:last-child) { border-bottom: 1px solid #f1f5f9; }
    .olx-smart-section-title {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        color: #94a3b8;
        padding: 4px 16px 6px 16px;
    }
    .olx-smart-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        color: #1e293b;
        text-decoration: none !important;
        transition: background-color 0.12s ease;
        cursor: pointer;
    }
    .olx-smart-item:hover, .olx-smart-item.active {
        background-color: #f8fafc;
    }
    .olx-smart-item .icon-box {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        font-size: 14px;
        flex-shrink: 0;
    }
    .olx-smart-item.cat-highlight .icon-box {
        background: #f3e8ff;
        color: #7c3aed;
    }
    .olx-smart-item .text-content {
        flex: 1;
        min-width: 0;
    }
    .olx-smart-item .main-text {
        font-size: 14px;
        font-weight: 500;
        color: #1e293b;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .olx-smart-item .sub-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }
    .olx-smart-item .cat-badge {
        display: inline-block;
        padding: 2px 8px;
        background: #ede9fe;
        color: #6d28d9;
        font-weight: 700;
        border-radius: 20px;
        font-size: 11px;
        margin-left: 6px;
    }
    .olx-smart-product-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 8px 16px;
        color: #1e293b;
        text-decoration: none !important;
        transition: background 0.12s ease;
    }
    .olx-smart-product-card:hover, .olx-smart-product-card.active {
        background-color: #f8fafc;
    }
    .olx-smart-product-thumb {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        object-fit: cover;
        background: #f1f5f9;
        flex-shrink: 0;
        border: 1px solid #e2e8f0;
    }
    .olx-smart-product-info {
        flex: 1;
        min-width: 0;
    }
    .olx-smart-product-title {
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.3;
    }
    .olx-smart-product-cat {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }
    .olx-smart-product-price {
        font-size: 14px;
        font-weight: 700;
        color: #16a34a;
        margin-left: 12px;
        flex-shrink: 0;
    }
    .olx-smart-footer {
        padding: 10px 16px;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        text-align: center;
    }
    .olx-smart-footer a {
        font-size: 13px;
        font-weight: 600;
        color: #7c3aed;
        text-decoration: none;
    }
    .olx-smart-footer a:hover {
        text-decoration: underline;
    }
    @media (max-width: 768px) {
        .olx-smart-dropdown {
            min-width: 100% !important;
            max-width: 100% !important;
            left: 0 !important;
            right: 0 !important;
        }
    }
    </style>';
}
osc_add_hook('header', 'olx_smart_search_header');


// -------------------------------------------------------------
// 6. INJECT JS ENGINE IN FOOTER
// -------------------------------------------------------------
function olx_smart_search_footer() {
    $ajax_url = osc_base_url(true) . '?page=smart_search_ajax';
    $search_url = osc_base_url(true) . '?page=search';
    ?>
    <script>
    (function() {
        const queryInput = document.querySelector('header #query, .main-search #query, #query');
        if (!queryInput) return;

        const parent = queryInput.parentNode;
        let wrapper = parent.classList.contains('olx-smart-wrapper') ? parent : null;
        if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'olx-smart-wrapper';
            wrapper.style.position = 'relative';
            wrapper.style.display = 'inline-block';
            wrapper.style.width = '100%';
            parent.insertBefore(wrapper, queryInput);
            wrapper.appendChild(queryInput);
        }

        let dropdown = document.querySelector('.olx-smart-dropdown');
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'olx-smart-dropdown';
            wrapper.appendChild(dropdown);
        }

        const cache = new Map();
        let debounceTimer = null;
        let currentAbort = null;
        let selectedIndex = -1;

        const ajaxUrl = '<?php echo $ajax_url; ?>';
        const searchBaseUrl = '<?php echo $search_url; ?>';

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text || '';
            return div.innerHTML;
        }

        function highlightMatch(text, query) {
            if (!text || !query) return escapeHtml(text);
            const safeText = escapeHtml(text);
            const q = escapeHtml(query).toLowerCase();
            let result = "";
            let start = 0;
            let lower = safeText.toLowerCase();
            let idx = lower.indexOf(q, start);
            if (idx === -1) return safeText;
            while (idx !== -1) {
                result += safeText.substring(start, idx);
                result += '<strong style="color: #7c3aed; font-weight: 700;">' + safeText.substring(idx, idx + q.length) + '</strong>';
                start = idx + q.length;
                idx = lower.indexOf(q, start);
            }
            result += safeText.substring(start);
            return result;
        }

        function renderDropdown(data, query) {
            if (!data || (!data.category && (!data.items || data.items.length === 0))) {
                dropdown.innerHTML = `
                    <div class="olx-smart-section">
                        <a href="${searchBaseUrl}&sPattern=${encodeURIComponent(query)}" class="olx-smart-item">
                            <div class="icon-box"><i class="fas fa-search"></i></div>
                            <div class="text-content">
                                <div class="main-text">Buscar por "<strong>${escapeHtml(query)}</strong>"</div>
                                <div class="sub-text">em Todas as categorias</div>
                            </div>
                        </a>
                    </div>
                `;
                dropdown.style.display = 'block';
                return;
            }

            let html = '';

            // Section 1: Suggestions
            html += '<div class="olx-smart-section">';
            html += '<div class="olx-smart-section-title">Sugestões de busca</div>';

            if (data.category) {
                const catUrl = `${searchBaseUrl}&sPattern=${encodeURIComponent(query)}&sCategory=${data.category.id}`;
                html += `
                    <a href="${catUrl}" class="olx-smart-item cat-highlight" data-index="0">
                        <div class="icon-box"><i class="${data.category.icon}"></i></div>
                        <div class="text-content">
                            <div class="main-text">Buscar "<strong>${escapeHtml(query)}</strong>" em <span class="cat-badge">${escapeHtml(data.category.name)}</span></div>
                            <div class="sub-text">Filtrar anúncios nesta categoria</div>
                        </div>
                        <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 12px;"></i>
                    </a>
                `;
            }

            const globalUrl = `${searchBaseUrl}&sPattern=${encodeURIComponent(query)}`;
            html += `
                <a href="${globalUrl}" class="olx-smart-item" data-index="${data.category ? 1 : 0}">
                    <div class="icon-box"><i class="fas fa-search"></i></div>
                    <div class="text-content">
                        <div class="main-text">Buscar "<strong>${escapeHtml(query)}</strong>"</div>
                        <div class="sub-text">em Todas as categorias</div>
                    </div>
                    <i class="fas fa-chevron-right" style="color: #cbd5e1; font-size: 12px;"></i>
                </a>
            `;
            html += '</div>';

            // Section 2: Product Cards
            if (data.items && data.items.length > 0) {
                html += '<div class="olx-smart-section">';
                html += `<div class="olx-smart-section-title">${data.category ? 'Anúncios em ' + escapeHtml(data.category.name) : 'Anúncios recentes'}</div>`;

                let itemIdxStart = data.category ? 2 : 1;
                data.items.forEach((item, idx) => {
                    const thumb = item.img ? `<img src="${item.img}" class="olx-smart-product-thumb" alt="" />` : `<div class="olx-smart-product-thumb" style="display:flex;align-items:center;justify-content:center;color:#94a3b8;"><i class="fas fa-image"></i></div>`;
                    html += `
                        <a href="${item.url}" class="olx-smart-product-card" data-index="${itemIdxStart + idx}">
                            ${thumb}
                            <div class="olx-smart-product-info">
                                <div class="olx-smart-product-title">${highlightMatch(item.title, query)}</div>
                                <div class="olx-smart-product-cat"><i class="fas fa-tag" style="font-size: 10px; margin-right: 4px;"></i>${escapeHtml(item.category)}</div>
                            </div>
                            <div class="olx-smart-product-price">${escapeHtml(item.price)}</div>
                        </a>
                    `;
                });
                html += '</div>';

                html += `
                    <div class="olx-smart-footer">
                        <a href="${data.category ? searchBaseUrl + '&sPattern=' + encodeURIComponent(query) + '&sCategory=' + data.category.id : globalUrl}">Ver todos os resultados &rarr;</a>
                    </div>
                `;
            }

            dropdown.innerHTML = html;
            dropdown.style.display = 'block';
            selectedIndex = -1;
        }

        function fetchSuggestions(query) {
            query = query.trim();
            if (query.length < 2) {
                dropdown.style.display = 'none';
                dropdown.innerHTML = '';
                return;
            }

            if (cache.has(query.toLowerCase())) {
                renderDropdown(cache.get(query.toLowerCase()), query);
                return;
            }

            if (currentAbort) {
                currentAbort.abort();
            }
            currentAbort = new AbortController();

            fetch(`${ajaxUrl}&q=${encodeURIComponent(query)}`, {
                signal: currentAbort.signal,
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'ok') {
                    cache.set(query.toLowerCase(), data);
                    renderDropdown(data, query);
                }
            })
            .catch(err => {
                if (err.name !== 'AbortError') {
                    console.error('Smart search error:', err);
                }
            });
        }

        queryInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const val = this.value;
            debounceTimer = setTimeout(() => {
                fetchSuggestions(val);
            }, 180);
        });

        queryInput.addEventListener('focus', function() {
            if (this.value.trim().length >= 2) {
                fetchSuggestions(this.value);
            }
        });

        queryInput.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.olx-smart-item, .olx-smart-product-card');
            if (dropdown.style.display === 'none' || items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % items.length;
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + items.length) % items.length;
                updateSelection(items);
            } else if (e.key === 'Enter') {
                if (selectedIndex >= 0 && items[selectedIndex]) {
                    e.preventDefault();
                    items[selectedIndex].click();
                }
            } else if (e.key === 'Escape') {
                dropdown.style.display = 'none';
            }
        });

        function updateSelection(items) {
            items.forEach((el, idx) => {
                if (idx === selectedIndex) {
                    el.classList.add('active');
                    el.scrollIntoView({ block: 'nearest' });
                } else {
                    el.classList.remove('active');
                }
            });
        }

        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    })();
    </script>
    <?php
}
osc_add_hook('footer', 'olx_smart_search_footer');

// -------------------------------------------------------------
// ADMIN MENU REGISTRATION FOR SEARCH QUERIES
// -------------------------------------------------------------
function olx_smart_search_admin_menu() {
    osc_admin_menu_settings(
        __('Search Queries Log', 'olx_smart_search'),
        osc_admin_render_plugin_url('olx_smart_search/admin_queries.php'),
        'olx_smart_search_queries_settings'
    );
    osc_admin_menu_stats(
        __('Search Queries', 'olx_smart_search'),
        osc_admin_render_plugin_url('olx_smart_search/admin_queries.php'),
        'olx_smart_search_queries_stats'
    );
}
osc_add_hook('init_admin', 'olx_smart_search_admin_menu');
