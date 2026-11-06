<?php
/*
 * Copyright 2014 Osclass
 * Copyright 2026 Osclass by OsclassPoint.com
 *
 * Osclass maintained & developed by OsclassPoint.com
 * You may not use this file except in compliance with the License.
 * You may download copy of Osclass at
 *
 *     https://osclass-classifieds.com/download
 *
 * Do not edit or add to this file if you wish to upgrade Osclass to newer
 * versions in the future. Software is distributed on an "AS IS" basis, without
 * warranties or conditions of any kind, either express or implied. Do not remove
 * this NOTICE section as it contains license information and copyrights.
 */


    // meta tag robots
    osc_add_hook('header','sigma_nofollow_construct');

    sigma_add_body_class('register');
    osc_enqueue_script('jquery-validate');
    osc_current_web_theme_path('header.php') ;

    // Fetch Brazilian regions map for CEP autocompletion
    $br_regions = Region::newInstance()->findByCountry('BR');
    $uf_map = array();
    if(is_array($br_regions)) {
        foreach($br_regions as $r) {
            $uf = strtoupper(trim($r['s_name_native']));
            if(!empty($uf)) {
                $uf_map[$uf] = array(
                    'id' => (int) $r['pk_i_id'],
                    'name' => $r['s_name']
                );
            }
        }
    }
?>
<div class="form-container form-horizontal form-container-box">
    <div class="header">
        <h1><?php _e('Register an account for free', 'sigma'); ?></h1>
    </div>
    <div class="resp-wrapper">
        <form name="register" action="<?php echo osc_base_url(true); ?>" method="post" >
            <input type="hidden" name="page" value="register" />
            <input type="hidden" name="action" value="register_post" />

            <?php osc_run_hook('user_pre_register_form'); ?>

            <ul id="error_list"></ul>
            <div class="control-group">
                <label class="control-label" for="name"><?php _e('Name', 'sigma'); ?></label>
                <div class="controls">
                    <?php UserForm::name_text(); ?>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="email"><?php _e('Email', 'sigma'); ?></label>
                <div class="controls">
                    <?php UserForm::email_text(); ?>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="password"><?php _e('Password', 'sigma'); ?></label>
                <div class="controls">
                    <?php UserForm::password_text(); ?>
                </div>
            </div>
            <div class="control-group">
                <label class="control-label" for="password-2"><?php _e('Repeat password', 'sigma'); ?></label>
                <div class="controls">
                    <?php UserForm::check_password_text(); ?>
                    <p id="password-error" style="display:none;">
                        <?php _e('The passwords do not match', 'sigma'); ?>
                    </p>
                </div>
            </div>

            <!-- CEP / Address Section -->
            <div class="control-group cep-group">
                <label class="control-label" for="zip"><?php _e('CEP', 'sigma'); ?> <span class="req">*</span></label>
                <div class="controls">
                    <div class="cep-field-group">
                        <input id="zip" type="text" name="zip" maxlength="9" placeholder="00000-000" autocomplete="postal-code" />
                        <span id="cep-loading" class="cep-indicator" style="display:none;">
                            <svg class="spinner" viewBox="0 0 50 50"><circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle></svg>
                            <span>Buscando...</span>
                        </span>
                    </div>
                    <span id="cep-status-msg" class="cep-helper" style="display:none;"></span>
                    <span class="cep-hint">Digite o CEP para preencher o endereço automaticamente</span>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="address"><?php _e('Endereço / Logradouro', 'sigma'); ?></label>
                <div class="controls">
                    <input id="address" type="text" name="address" placeholder="Rua, Avenida, Praça..." />
                </div>
            </div>

            <div class="control-group-row">
                <div class="control-group col-half">
                    <label class="control-label" for="address_number"><?php _e('Número', 'sigma'); ?></label>
                    <div class="controls">
                        <input id="address_number" type="text" name="address_number" placeholder="Ex: 123" />
                    </div>
                </div>
                <div class="control-group col-half">
                    <label class="control-label" for="cityArea"><?php _e('Bairro', 'sigma'); ?></label>
                    <div class="controls">
                        <input id="cityArea" type="text" name="cityArea" placeholder="Bairro" />
                    </div>
                </div>
            </div>

            <div class="control-group-row">
                <div class="control-group col-half">
                    <label class="control-label" for="city"><?php _e('Cidade', 'sigma'); ?></label>
                    <div class="controls">
                        <input id="city" type="text" name="city" placeholder="Cidade" readonly />
                        <input id="cityId" type="hidden" name="cityId" value="" />
                    </div>
                </div>
                <div class="control-group col-half">
                    <label class="control-label" for="region"><?php _e('Estado (UF)', 'sigma'); ?></label>
                    <div class="controls">
                        <input id="region" type="text" name="region" placeholder="Estado (UF)" readonly />
                        <input id="regionId" type="hidden" name="regionId" value="" />
                        <input id="countryId" type="hidden" name="countryId" value="BR" />
                        <input id="country" type="hidden" name="country" value="Brazil" />
                        <input id="d_coord_lat" type="hidden" name="d_coord_lat" value="" />
                        <input id="d_coord_long" type="hidden" name="d_coord_long" value="" />
                    </div>
                </div>
            </div>

            <?php osc_run_hook('user_register_form'); ?>

            <div class="control-group"><?php osc_show_recaptcha('register'); ?></div>

            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-primary"><?php _e("Create account", 'sigma'); ?></button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php UserForm::js_validation(); ?>

<script type="text/javascript">
(function($) {
    var ufRegionMap = <?php echo json_encode($uf_map); ?>;
    var lastCep = '';

    function formatCep(val) {
        val = (val || '').replace(/\D/g, '');
        if (val.length > 5) {
            val = val.substring(0, 5) + '-' + val.substring(5, 8);
        }
        return val.substring(0, 9);
    }

    function setCepStatus(status, text) {
        var $status = $('#cep-status-msg');
        var $loading = $('#cep-loading');
        
        if (status === 'loading') {
            $loading.show();
            $status.hide();
        } else {
            $loading.hide();
            if (status === 'success') {
                $status.attr('class', 'cep-helper cep-success').text(text).show();
            } else if (status === 'error') {
                $status.attr('class', 'cep-helper cep-error').text(text).show();
            } else {
                $status.hide();
            }
        }
    }

    function searchCep(rawCep) {
        var cep = (rawCep || '').replace(/\D/g, '');
        if (cep.length !== 8) {
            return;
        }
        if (cep === lastCep) {
            return;
        }
        lastCep = cep;

        setCepStatus('loading');

        // ViaCEP primary lookup
        $.ajax({
            url: 'https://viacep.com.br/ws/' + cep + '/json/',
            dataType: 'json',
            timeout: 6000
        }).done(function(data) {
            if (data && !data.erro) {
                applyCepData({
                    street: data.logradouro || '',
                    neighborhood: data.bairro || '',
                    city: data.localidade || '',
                    ibge: data.ibge || '',
                    uf: (data.uf || '').toUpperCase(),
                    lat: '',
                    lng: ''
                });
            } else {
                fallbackBrasilApi(cep);
            }
        }).fail(function() {
            fallbackBrasilApi(cep);
        });
    }

    function fallbackBrasilApi(cep) {
        $.ajax({
            url: 'https://brasilapi.com.br/api/cep/v2/' + cep,
            dataType: 'json',
            timeout: 6000
        }).done(function(data) {
            if (data && (data.street || data.city || data.state)) {
                var ibgeCode = '';
                var lat = '';
                var lng = '';
                if (data.ibge && data.ibge.city) {
                    ibgeCode = data.ibge.city;
                }
                if (data.location && data.location.coordinates) {
                    lat = data.location.coordinates.latitude || '';
                    lng = data.location.coordinates.longitude || '';
                }
                applyCepData({
                    street: data.street || '',
                    neighborhood: data.neighborhood || '',
                    city: data.city || '',
                    ibge: ibgeCode,
                    uf: (data.state || '').toUpperCase(),
                    lat: lat,
                    lng: lng
                });
            } else {
                onCepNotFound();
            }
        }).fail(function() {
            onCepNotFound();
        });
    }

    function onCepNotFound() {
        setCepStatus('error', 'CEP não encontrado. Por favor preencha os campos manualmente.');
        $('#city, #region').prop('readonly', false);
    }

    function applyCepData(info) {
        if (info.street) {
            $('#address').val(info.street);
        }
        if (info.neighborhood) {
            $('#cityArea').val(info.neighborhood);
        }
        if (info.city) {
            $('#city').val(info.city);
        }
        if (info.ibge) {
            $('#cityId').val(info.ibge);
        }
        if (info.lat) {
            $('#d_coord_lat').val(info.lat);
        }
        if (info.lng) {
            $('#d_coord_long').val(info.lng);
        }

        if (info.uf && ufRegionMap[info.uf]) {
            var reg = ufRegionMap[info.uf];
            $('#region').val(reg.name + ' (' + info.uf + ')');
            $('#regionId').val(reg.id);
        } else if (info.uf) {
            $('#region').val(info.uf);
        }

        setCepStatus('success', '✓ Endereço localizado com sucesso!');

        // Direct user to enter door number if street exists
        if (info.street) {
            $('#address_number').focus();
        } else {
            $('#address').focus();
        }
    }

    function syncAddressNumber() {
        var addr = $.trim($('#address').val());
        var num = $.trim($('#address_number').val());
        if (num && addr) {
            var regex = new RegExp('(,\\s*)?' + num.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '$');
            if (!regex.test(addr)) {
                $('#address').val(addr.replace(/,\s*$/, '') + ', ' + num);
            }
        }
    }

    $(document).ready(function() {
        $('#zip').on('input keyup', function() {
            var formatted = formatCep($(this).val());
            $(this).val(formatted);
            var clean = formatted.replace(/\D/g, '');
            if (clean.length === 8) {
                searchCep(clean);
            } else {
                setCepStatus('none');
            }
        });

        $('#zip').on('blur', function() {
            var clean = $(this).val().replace(/\D/g, '');
            if (clean.length === 8) {
                searchCep(clean);
            }
        });

        $('#address_number').on('input change blur', function() {
            syncAddressNumber();
        });

        $('form[name=register] button[type=submit], form[name=register] input[type=submit]').on('click', function() {
            syncAddressNumber();
        });

        $('form[name=register]').on('submit', function() {
            syncAddressNumber();
        });
    });
})(jQuery);
</script>
<?php osc_current_web_theme_path('footer.php') ;
