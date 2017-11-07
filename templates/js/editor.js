(function() {

    /* TODO: Define class and id selectors as constants (but use 'var') */
    var LANGUAGES_PREVIEW   = 'zedit-preview';
    var LANGUAGES_RAW       = 'zedit-raw';
    var LANGUAGES_RCONTENT  = 'zedit-raw-content';
    var LANGUAGES_SHORTCUT  = 'zedit-shortcut';
    var LANGUAGES_SELECTED  = 'zedit-shortcut-selected';
    var LANGUAGES_EDITOR    = 'zedit-editor';

    /*
    * Structure of the editor:

    <lang>                  = language as two chars e.g. en -> english
    <content_unparsed>      = unparsed markdown for this language
    <content_parsed>        = parsed content of language

    { ... }                 = Only one language-shortcut can have this

    #languages-raw
        #languages-raw-<lang> .languages-raw:
            .languages-shortcut { #languages-shortcut-selected }: 
                innerHTML = <lang> 
            #languages-raw-<lang>-content .languages-raw-content:
                innerHTML = <content_unparsed>

        #languages-raw-<lang> .languages-raw:
            .languages-shortcut { #languages-shortcut-selected }: 
                innerHTML = <lang> 
            #languages-raw-<lang>-content .languages-raw-content:
                innerHTML = <content_unparsed>

        ...
    
    #languages-preview
        #languages-preview-<lang> .languages-preview:
            innerHTML = <content_parsed>
        
        #languages-preview-<lang> .languages-preview:
            innerHTML = <content_parsed>

        ...
    */

    var editor;

    /* Return all corresponding objects for one language */
    function language(lang) {
        return {
            raw: document.getElementById(LANGUAGES_RAW + '-' + lang + '-content'),
            preview: document.getElementById(LANGUAGES_PREVIEW + '-' + lang),
            shortcut: document.getElementById(LANGUAGES_RAW + '-' + lang),
        }
    }

    /* Get parsed contents as list */
    function previews() {
        return document.getElementById(LANGUAGES_PREVIEW).getElementsByClassName(LANGUAGES_PREVIEW);
    }

    /* Toggle system event */
    function defuse(event) {
        event.stopPropagation();
        event.preventDefault();
    }    

    /* Remove one language */
    function remove(e) {

        defuse(e);                

        var lang = e.target.parentNode.getElementsByClassName(LANGUAGES_SHORTCUT)[0].innerHTML;
        var tab = language(lang);

        /* If raw and preview are empty -> delete without asking */
        if((tab.raw.innerHTML === "" && tab.preview.innerHTML === "") 
            || confirm('Really delete?')) {
            tab.raw.parentNode.removeChild(tab.raw);
            tab.preview.parentNode.removeChild(tab.preview);
            tab.shortcut.parentNode.removeChild(tab.shortcut);
        }

    }

    /* Hide every buffer except selected one */
    function hide_buffers() {
        /* 
        * TODO: Optimization - Only change to none where 'display' is initial 
        */
        var prevs = previews();
        for(var i = 0; i < prevs.length; i++) {
            prevs[i].style.display = 'none';
        }
    }

    /* Gets event from div click */
    function switch_to(lang) {
        
        /* Hide all other previews */
        hide_buffers();

        var tab = language(lang);

        editor.set({
            sin: tab.raw,
            sout: tab.preview,
        });
        
        /* Makes the newly selected buffer visible */
        tab.preview.style.display = 'initial';

        /* TODO: Send event to frontend for marking shortcut */
        var next_tab = tab.shortcut.getElementsByClassName(LANGUAGES_SHORTCUT)[0];
        var previous_tab = document.getElementById(LANGUAGES_SELECTED);
        if(previous_tab) {
            previous_tab.id = '';
        }   

        next_tab.id = LANGUAGES_SELECTED;

    }

    function create(parent, lang) {
        var node = document.createElement('div');
        node.id = parent.id + '-' + lang;
        node.className = parent.id;

        /* For consistency, this will also be checked before call in 'add' */
        if(document.getElementById(node.id)) {
            throw 'Language already added';
        }

        parent.appendChild(node);
        return node;
    }

    function contentable(node) {
        var content = document.createElement('div');
        content.id = node.id + '-content';
        /* TODO: create dynamically */
        content.className = LANGUAGES_RCONTENT;
        content.style.display = 'none';
        node.appendChild(content);
        return node;
    }

    function closeable(node) {
        var cross = document.createElement('button');
        cross.innerHTML = 'X';
        cross.addEventListener("click", remove);
        node.appendChild(cross);
        return node;
    }

    var file_counter = 0;
    function add_file() {

        file_counter += 1;

        var node = document.createElement('div');
        node.className = 'addfile-content';

        var selector = document.createElement('input');
        selector.type = 'file';
        selector.name = selector.type + file_counter;

        var close = document.createElement('button');
        close.innerHTML = 'X';
        close.onclick = function(e) {
            defuse(e);
            console.log(e);
            e.target.parentNode.parentNode.removeChild(e.target.parentNode);
        };

        node.appendChild(selector);
        node.appendChild(close);

        document.getElementById('addfile').appendChild(node);

    }

    function add(lang) {

        var fixedlang = lang.toLowerCase().trim();

        try {

            if(fixedlang.length === 0) {
                throw 'Language must be filled';
            }

            if(document.getElementById(LANGUAGES_RAW + '-' + fixedlang)) {
                throw 'Language already exists';
            }

            /* 
            * Create stuff under languages-raw:
            * - Shortcut
            * - Hidden buffer for markdown text
            */
            var pane = create(document.getElementById(LANGUAGES_RAW), fixedlang);
            pane.addEventListener("click", function(e) {
                defuse(e);

                /* TODO: is this check neccessary */
                var lang = e.target.className === LANGUAGES_SHORTCUT
                            ? e.target.innerHTML
                            : e.target.getElementsByClassName(LANGUAGES_SHORTCUT)[0].innerHTML;

                switch_to(lang);
            });
            /* Would overwrite button if closeable was called before! */
            pane.innerHTML = '<span class="' + LANGUAGES_SHORTCUT + '">' + fixedlang + '</span>';
            closeable(contentable(pane));

            try {
                var preview = create(document.getElementById(LANGUAGES_PREVIEW), fixedlang);
                preview.style.visible = 'none';
                /* TODO: Is this required? */
                preview.elementName = 'entry_' + fixedlang;
            } catch(e) {
                /* Ignore creation of output buffers because check should happen in first create */
            }

            document.getElementById('addlang-name').value = '';

            switch_to(lang);

        } catch(e) {

            document.getElementById('addlang-name').focus();
            alert(e);

        }

    }

    function on_submit(form) {

        /* 
        * TODO: Do checks
        * If one buffer is empty -> confirm   
        */
        var request = new XMLHttpRequest();
        request.open('POST', form.action);
        request.addEventListener('load', function(e) {
            console.log(e.target.response);
        });

        /* Parse markdown before submitting */
        var data = new FormData(form);
        var raws = document.getElementById(LANGUAGES_RAW).getElementsByClassName(LANGUAGES_RCONTENT);

        for(var i = 0; i < raws.length; i++) {
            /* 
            * TODO: Make this generic
            * Comes in like: xxxxx-xxxxx-en-xxxxxxx 
            */
            var name = raws[i].id.split('-')[2];
            var txt = editor.parse_text(raws[i].innerHTML); 
            data.append('entry_' + name, txt);
        }

        request.send(data);

    }

    /*
    * Preview containers can be pregenerated by php files and parsed to markdown afterwards
    */
    function init_from_previews() {

        var initial_previews = document.getElementsByClassName(LANGUAGES_PREVIEW);
        for(var i = 0; i < initial_previews.length; i++) {
            var lang = initial_previews[i].id.split(LANGUAGES_PREVIEW + '-')[1];
            add(lang);
        }

        var prevs = previews();
        if(prevs.length > 0) {
            var lang = previews()[0].id.split(LANGUAGES_PREVIEW + '-')[1];
            switch_to(lang);
        }

    }

    function init() {

        editor = zedit({
            editorNode: document.getElementById(LANGUAGES_EDITOR),
            onsubmit: on_submit,
        });

        document.getElementById('addfile-confirm').addEventListener('click', function(e) {
            defuse(e);
            add_file();
        });

        document.getElementById('addlang-confirm').addEventListener('click', function(e) {
            defuse(e);
            add(document.getElementById('addlang-name').value);
        });

        document.getElementById('addlang-name').addEventListener('keydown', function(e) {
            /* 
            * If there is one open tab already and enter was hit to create a new one,
            * this will somehow cause a close event on every tab.
            * So we defuse every submit on addlang-name
            */
            if(e.keyCode === 13) {
                defuse(e);
                add(e.target.value);
            }
        });

        init_from_previews();

    }

    document.addEventListener('DOMContentLoaded', init);

}())