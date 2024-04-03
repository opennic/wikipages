# Dokuwiki pageredirect plugin change log

When writing entries, refer to [Keep a CHANGELOG](http://keepachangelog.com/) for guidelines.

All notable changes to this project will be documented in this file.

## [UNRELEASED]

[UNRELEASED]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20221120...master

## [20221120]

- Make external redirects optional, #43
- Add Move plugin support, #40

[20221120]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20220103...20221120

## [20220103]

- Partial support for backlinks with redirects by @michitux in #26
- Esperanto translation in #30
- Add informal german language by @ebroda in #33
- Add redirection target to link metadata by @alexdraconian in #39

[20220103]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20170512...20220103

## [20170512]

  - plugin now requires dokuwiki 2014-05-05 (Ponder Stibbons)
  - revert broken implementation of adding `GET` parameters to url from [#16], fixes [#24], [#25]
  - do not add parameters to url if the redirect is external. [133a129], [#25]
  - fix fatal error when showing redirected message. [a04bcd9], [#23]
  - fix plugin conflict with creole plugin. [36ebf4a], [#18]
  - resolve page names to be absolute. [#19]
  - added german translation [#27]

[20170512]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20160924...20170512
[#18]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/18
[#19]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/19
[#23]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/23
[#24]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/24
[#25]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/25
[#27]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/27
[133a129]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/133a129
[36ebf4a]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/36ebf4a
[a04bcd9]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/a04bcd9

## [20160924]

  - add French translations. [#14]
  - display pagename if heading is undefined. [#15], [#9], [#13]
  - preserve `GET` parameters when redirecting. [#16]
  - add Russian translations. [#17]
  - update Korean translations. [#21]
  - fix test which is not passing with new version of Dokuwiki. [#22]

[20160924]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20140414...20160924
[#13]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/13
[#14]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/14
[#15]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/15
[#16]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/16
[#17]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/17
[#21]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/21
[#22]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/22
[#9]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/9

## [20140414]

  - add `<br/>` tag to the redirect note to avoid being covered by page TOC. [#4]
  - add Japanese translations. [#7]
  - make `~~REDIRECT` pattern non-greedy. [#5], [#8]
  - honour `conf['useheading']` for redirect note. [#6], [#8]
  - basic support for external redirects. [#8]
  - fix access to protected variable after [dokuwiki#555]. [#10], [#11]

[20140414]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20120816...20140414
[dokuwiki#555]: https://github.com/splitbrain/dokuwiki/pull/555
[#10]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/10
[#11]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/11
[#4]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/4
[#5]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/5
[#6]: https://github.com/glensc/dokuwiki-plugin-pageredirect/issues/6
[#7]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/7
[#8]: https://github.com/glensc/dokuwiki-plugin-pageredirect/pull/8

## [20120816]

  - allow `#redirect` syntax to be lowercase, but it must be start on line. [1362442]

[20120816]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20120612...20120816
[1362442]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/1362442

## [20120612]

  - match anything in page name, to wiki path it is converted internally. [f423934]
  - preserve `#section` anchors on redirect. [c31b525]
  - make redirects 301 redirect permanently to be SEO friendly, [9796335]
  - apply prevent conflict patch from wiki comments. [87145da]
  - add alternative `#REDIRECT namespace/pagename` syntax. [01efce2]
  - add zh-tw translations. [82539ae]
  - add Korean translations. [6aa688d]
  - add portugese translations. [e22f33a]
  - fix matching page with `#REDIRECT` syntax. [4dca632]

[20120612]: https://github.com/glensc/dokuwiki-plugin-pageredirect/compare/20070124...20120612
[01efce2]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/01efce2
[4dca632]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/4dca632
[6aa688d]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/6aa688d
[82539ae]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/82539ae
[87145da]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/87145da
[9796335]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/9796335
[c31b525]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/c31b525
[e22f33a]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/e22f33a
[f423934]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commit/f423934

## [20070124]

  - Build 2

[20070124]: https://github.com/glensc/dokuwiki-plugin-pageredirect/commits/20070124
