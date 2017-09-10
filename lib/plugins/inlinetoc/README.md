# This plugin is not maintained anymore

---

This plugin renders the toc of a page inside the page content, a la Mediawiki.

Sample
======

    {{INLINETOC}}

Result:

![Sample](https://github.com/Andreone/dokuwiki_inlinetoc/blob/master/sample.png)

Note
====

The plugin replaces the tag with a div. The div's class is inlinetoc**2** (the
css is in inlinetoc/all.css file). The 2 is here to not enter in conflict with
the TOC plugin which already use the class inlinetoc.

The plugin won't work if you specify *{{NOTOC}}* on the page because it relies
on dokuwiki's internal toc processor to build the page's toc.

Credits
=======

-   This plugin is largely inspired by the TOC
    plugin (http://www.dokuwiki.org/plugin:toc)
