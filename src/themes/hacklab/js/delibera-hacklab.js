jQuery(document).ready(function() {
    if (delibera.situation == 'emvotacao') {
        // ajusta o estilo do botão de votação dos encaminhamentos de uma pauta
        jQuery('.encaminhamentos .submit #submit').addClass('btn btn-success');
    }
    
    if (delibera.situation == 'comresolucao') {
        jQuery('.comentario_coluna2').hide();
    }
});
