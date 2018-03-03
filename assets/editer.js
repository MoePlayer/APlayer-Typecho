$(function() {
	if($('#wmd-button-row').length>0)$('#wmd-button-row').append('<li class="wmd-spacer wmd-spacer1" id="wmd-spacer5"></li><li class="wmd-button" id="wmd-meting-button" style="" title="插入音乐"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAMAAAAoLQ9TAAABBVBMVEUAAADS1NVVVVVKS0pWVldZWVpJSkrHyctlZmaxs7RlZmZgYWFfX1/GyMrLzc7P0dJiY2PO0NJaWlqWmJpYV1dVVVXQ09TIystJSkpXV1fMzs9QUFCYmpxVVla3ubmoqqqdnp+jpaaSlJZqamtmZmbP0dLCxMbBw8TLzc65vL1VV1dSU1PBw8XFx8nR09SOkJKipaeho6RjZWW7vL2ys7SusLHGyMpZWVp4eHisrrBqamrCxMapq6yusLJ5enrP0tNtbm+ipafGyMpJSkqmp6iZmptXV1dZWVnJy8zS1NWmqKrLzc7R09TFx8m3ubuqrK6hpKWcnqCVlpeXmZuSk5SDhIWDg4QW1XxpAAAATXRSTlMABEgFAvxPFv79+vrGsrGurpKNaFlOTTQ0MyMc/v367e3p6OPizczMyLetrKeemYSDg4F/fn59fXt5eW5ubGtpZ2BdTEI+NzYrKBYMC1kKAkAAAAC1SURBVBjTRY7VEoMwEEWh7sUKFOru7u6OVv7/U7oh7bAPOblnktlL/IYkMfE8eDoSofn7P1+jKfP9efnDF5ylipFnOI4pmGXJ+t1Kh87oEg9lmiRQKB5k/FQ+lgTAOmovaMzhGPQJpwsll5OYdoGzFVsTkRDr7GYMnGR1tweJfTKR6wGHioaFw/3U28CRogaxCBp6B7hT1EDMEgFN2wK9YdWHhc9fvRHILKmT1ZRaeHEduwfMF0K7E1YSv1vLAAAAAElFTkSuQmCC"/></li>');
	$(document).on('click', '#wmd-meting-button', function() {
        $('body').append(
            '<div id="MetingPanel">'+
				'<div class="wmd-prompt-background" style="position: absolute; top: 0px; z-index: 1000; opacity: 0.5; height: 875px; left: 0px; width: 100%;"></div>'+
                '<div class="wmd-prompt-dialog">'+
                    '<div>'+
                        '<p><b>插入音乐</b></p>'+
                        '<p>请在下方的输入框内输入要插入的音乐地址'+
                        '<p><input type="text"></input></p>'+
                    '</div>'+
                    '<form>'+
    					'<button type="button" class="btn btn-s primary" id="ok">确定</button>'+
                        '<button type="button" class="btn btn-s" id="cancel">取消</button>'+
                    '</form>'+
				'</div>'+
			'</div>');
        $('.wmd-prompt-dialog input').val('http://').select();
	});
    $(document).on('click','#cancel',function() {
        $('#MetingPanel').remove();
        $('textarea').focus();
    });
    $(document).on('click','#ok',function() {
        callback=$.ajax({
            type:'POST',
            url:murl,
            data:{data:$('.wmd-prompt-dialog input').val()},
            async:false
        });
        $('#MetingPanel').remove();
        myField = document.getElementById('text');
		if (document.selection) {
			myField.focus();
			sel = document.selection.createRange();
			sel.text = callback.responseText;
			myField.focus();
		}
        else if (myField.selectionStart || myField.selectionStart == '0') {
			var startPos = myField.selectionStart;
			var endPos = myField.selectionEnd;
			var cursorPos = startPos;
			myField.value = myField.value.substring(0, startPos)
			+ callback.responseText
			+ myField.value.substring(endPos, myField.value.length);
			cursorPos += callback.responseText.length;
			myField.focus();
			myField.selectionStart = cursorPos;
			myField.selectionEnd = cursorPos;
		}
        else{
			myField.value += callback.responseText;
			myField.focus();
		}
    });
});
