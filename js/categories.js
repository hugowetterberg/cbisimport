(function($) {
  $(document).ready(function() {
    var tregex = /,\s+/;
    $('.cbis-category').each(function() {
      var input = $(this);
      $('label span', this.parentNode).click(function(){
        var tag = $(this).text().toLowerCase(), i, 
          tags = input.val().split(tregex), tcount = tags.length, found = false;
        if (!tags[0].length) {
          tags = [];
        }
        for (i=0; !found && i<tcount; i++) {
          found = tags[i] == tag;
        }
        if (!found) {
          tags.push(tag);
          input.val(tags.join(', '));
        }
      }).css({'cursor': 'pointer'});

      // Add fill link
      if ($(this).hasClass('has-children')) {
        $('<a>' + Drupal.t('Fill') + '</a>').click(function() {
          var i, catid, classes = input.attr('class').split(' ');
          for (i=0; i<classes.length; i++) {
            if (classes[i].substr(0, 11) == 'category-id') {
              (function(parent, val){
                $('.category-parent-' + parent).each(function() {
                  var here = $(this).val().split(tregex), 
                    i, j, hc = here.length, vc = val.length, found = false;
                  if (!here[0].length) {
                    here = [];
                  }
                  for (i=0; i<vc; i++) {
                    for (j=0; !found && j<hc; j++) {
                      found = here[j] == val[i];
                    }
                    if (!found) {
                      here.push(val[i]);
                    }
                    found = false;
                  }
                  $(this).val(here.join(', '));
                });
              })(classes[i].substr(12), input.val().split(tregex));
            }
          }
        }).insertAfter(input);
      }
    });
  });
})(jQuery);