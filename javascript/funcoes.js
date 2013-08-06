jQuery.noConflict();
(function($) {
    $(document).ready(function(){

        var campo_atual = null;

        $('#tabela-repositorios').on('click', '.td-nome, .td-url', function(e){

                var classe = $(this).attr('class');
                var campo = null;
                var id = null;

                if (classe == 'td-nome') {
                    campo = 'nome';
                    id = $(this).attr('id').substr(8);
                } else {
                    campo = 'url';
                    id = $(this).attr('id').substr(7);
                }

                if (campo_atual != $(this).attr('id')) {
                    campo_atual = $(this).attr('id');
                    var valor = $(this).text();
                    $(this).html('');
                    $(this).html('<input type="text" id="campo-'+campo+'-'+id+'" value="'+valor+'" size="'+ valor.length +'" />');
                    $('#campo-'+campo+'-'+id).focus();
                }
            }
        );

        $('#tabela-repositorios').on('blur', 'input', function(){
            campo_atual = null;
            var valor = $(this).val();
            var array = $(this).attr('id');
           // console.log(array);
            array = array.split('-');
            var id = array[2];
            var campo = array[1];
            $('#td-'+campo+'-'+id).html('').html(valor);
            montarJSON();
        });

        $('.td-excluir a').on('click', function(e){
            e.preventDefault();
        });

        var nome = $( "#nome" ),
            url = $( "#url" ),
            allFields = $( [] ).add( nome ).add( url ),
            tips = $( ".validateTips" );

        $( "#form-cadastro" ).dialog({
            autoOpen: false,
            height: 300,
            width: 350,
            modal: true,
            buttons: {
                "Adicionar": function() {
                    var certo = true
                    allFields.removeClass( "ui-state-error" );

                    allFields.each(function(i){
                        if ($(this).val() == '') {
                            certo = false;
                            $(this).addClass( "ui-state-error" );
                        }
                    });

                    if ( certo ) {
                        var $tr_dados = $('#tabela-repositorios tbody .tr-dados');
                        var totalTD  = $tr_dados.length;
                        if (totalTD == 0) {
                            var indice = 0;
                        } else {
                            var indice = $tr_dados.eq(totalTD-1).children('td').eq(0).attr('id');
                            indice = parseInt(indice.substr(8))+1;

                        }
                       // console.log(indice)
                        $('.td-nenhum').parent().remove();
                        $( "#tabela-repositorios tbody" ).append('<tr  class="tr-dados">' +
                            '<td style="text-align:  center;" class="td-nome" id="td-nome-'+indice+'">'+nome.val()+'</td>' +
                            '<td style="text-align:  center;" class="td-url" id="td-url-'+indice+'">'+url.val()+'</td>' +
                            '<td style="text-align:  center;" class="td-excluir"><span><a class="bt-excluir" href="#"> Excluir</a></span></td>' +
                            '</tr>' );
                        montarJSON();

                        $( this ).dialog( "close" );
                    }
                },
                'Cancelar': function() {
                    $( this ).dialog( "close" );
                }
            },
            close: function() {
                allFields.val( "" ).removeClass( "ui-state-error" );
            }
        });

        $('#botao-cadastrar').on('click',function(e) {
            e.preventDefault();
            $( "#form-cadastro" ).dialog('open');
        });

        $('#tabela-repositorios').on('click', '.bt-excluir', function(e){
            e.preventDefault();
            $(this).parent().parent().parent().remove();

            var totalTD = $('#tabela-repositorios tbody .tr-dados');
            totalTD  = totalTD.length;
           // console.log(totalTD)
            if (totalTD == 0) {
                $( "#tabela-repositorios tbody" ).append('<tr>' +
                    '<td class="td-nenhum" colspan="3" style="text-align: center">Nenhum repositório cadastrado.</td>' +
                    '</tr>' );
            }
            montarJSON();
        });

    });

    function montarJSON() {
        var $trs= $('#tabela-repositorios tbody tr.tr-dados');
        var json = '';
        var array = new Array();
        $trs.each(function(i){
            array[i]= {}
            array[i].nome = $(this).children('.td-nome').eq(0).text();
            array[i].url = $(this).children('.td-url').eq(0).text();
        });
        $('#hidden-repositorios').val(JSON.stringify(array));
      //  console.log(JSON.stringify(array));
    }

})(jQuery);
