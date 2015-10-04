$(function() {

    ace.config.set("packaged", true);
    ace.config.set("basePath", 'https://cdnjs.cloudflare.com/ajax/libs/ace/1.2.0');

    var jadeEditor = ace.edit('jadeEditor');
    var phpEditor = ace.edit('phpEditor');

    jadeEditor.setTheme('ace/theme/xcode');
    jadeEditor.getSession().setMode('ace/mode/jade');
    jadeEditor.getSession().setUseWorker(false)
    jadeEditor.getSession().setUseSoftTabs(true);
    jadeEditor.setValue(!{$exampleJade});
    jadeEditor.navigateFileStart();
    phpEditor.setTheme('ace/theme/xcode');
    phpEditor.getSession().setMode('ace/mode/php');
    phpEditor.getSession().setUseWorker(false)
    phpEditor.getSession().setUseSoftTabs(true);
    phpEditor.setReadOnly(true);

    function compile()
    {

        document.getElementById('phpEditor').classList.add('compiling');

        var eh = !{$handleErrors} ? '&withErrorHandler' : '';
        var m = !{$minify} ? '&minify' : '';
        $.post('!{$url}?' + eh + m, {jade: jadeEditor.getValue()}, function(result) {

            document.getElementById('phpEditor').classList.remove('compiling');
            phpEditor.setValue(JSON.parse(result));
            phpEditor.navigateFileStart();
        });
    }

    var iv;
    function changed()
    {

        if (iv) {

            window.clearTimeout(iv);
        }

        document.getElementById('phpEditor').classList.add('compiling');
        iv = window.setTimeout(compile, 500);
    }

    jadeEditor.getSession().on('change', changed);
    compile();
});