jQuery(document).ready(function() {

  jQuery(".delibera-like-link").click(function() {
    var container = jQuery(this);
    if (container.hasClass('.active'))
    {
      jQuery.post(
        delibera.ajax_url,
        {
          action : "delibera_descurtir",
          like_id : jQuery(this).children('input[name="object_id"]').val(),
          type : jQuery(this).children('input[name="type"]').val(),
        },
        function(response) {
          container.removeClass('active');
          jQuery(container).parent().children('.delibera-like-count').text(response);
        }
      );
    }
    else if(jQuery(this).children('input[name="object_id"]').val() > 0)
    {
      jQuery.post(
        delibera.ajax_url,
        {
          action : "delibera_curtir",
          like_id : jQuery(this).children('input[name="object_id"]').val(),
          type : jQuery(this).children('input[name="type"]').val(),
        },
        function(response) {
          container.addClass('active');
          jQuery(container).parent().children('.delibera-like-count').text(response);
        }
      );
    }
  });

  jQuery(".delibera-unlike-link").click(function() {
    var container = jQuery(this);
    if(jQuery(this).children('input[name="object_id"]').val() > 0)
    {
      jQuery.post(
        delibera.ajax_url,
        {
          action : "delibera_discordar",
          like_id : jQuery(this).children('input[name="object_id"]').val(),
          type : jQuery(this).children('input[name="type"]').val(),
        },
        function(response) {
          jQuery(container).parent().children('.delibera-unlike-count').text(response);
        }
      );
    }
  });
});
