$(document).ready(function() {
    $('input.jSignature').each(function(){
        var $field = $(this).hide();
        var $jsig = $('<div class="jSignature-injected"></div>').insertAfter($field);
        var $clear = $('<a class="jSignature-clear" href="#">clear drawing</a>');
        var $type = $('<a class="jSignature-type" href="#">type signature instead</a>');
        $('<div class="digraph-actionbar active"></div>').insertAfter($jsig)
            .append($type)
            .append($clear);
        // clear signature button
        $clear.on('click',function(e){
            $jsig.jSignature('reset');
            $field.val('');
            e.preventDefault();
        });
        // toggle between typing and drawing
        $type.on('click',function(e){
            if ($type.is('.typing')) {
                // toggle to drawing mode
                $type.removeClass('typing').text('type my signature instead');
                $clear.show();
                $field.hide();
                $jsig.show();
                $field.val($field.attr('data-jsig'));
            }else {
                // toggle to typing mode
                $type.addClass('typing').text('draw my signature instead');
                $clear.hide();
                $field.show();
                $jsig.hide();
                if ($field.attr('value') && $field.attr('value').substring(0,24) != 'image/jsignature;base30,') {
                    $field.val($field.attr('value'));
                }else {
                    $field.val('');
                }
            }
            e.preventDefault();
        });
        // set up jSignature
        $jsig.jSignature();
        if ($field.val()) {
            if ($field.val().substring(0,24) != 'image/jsignature;base30,') {
                $type.trigger('click');
            }else if ($field.val() != 'image/jsignature;base30,') {
                $field.attr('data-jsig',$field.val());
                $jsig.jSignature('setData','data:'+$field.val());
            }
        }
        $jsig.bind('change',function(){
            var data = $jsig.jSignature('getData','base30');
            if (data != 'image/jsignature;base30,') {
                $field.val(data);
                $field.attr('data-jsig',data);
            }
        });
    });
});