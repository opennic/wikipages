/**
 * @file    divalign2/script.js
 * @brief   Adds alignment picker to edit toolbar in Divalign2 plugin.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Luis Machuca Bezzaza <luis [dot] machuca [at] gulix [dot] cl>
 */
/* array[key]= insertion string , [value] = icon filename. */

/* This defines a picker button.
 * Because of the way picker buttons work in DokuWiki, the align buttons
 * can not be used with selected text.
 */

if(window.toolbar!=undefined){
  var align_da2_arr = new Array(); 
  align_da2_arr['#;;\nParagraph\n#;;\n']    = 'pleft.png';
  align_da2_arr[';#;\nParagraph\n;#;\n']    = 'pcenter.png';
  align_da2_arr[';;#\nParagraph\n;;#\n']    = 'pright.png';
  align_da2_arr['###\nParagraph\n###\n']    = 'pjustify.png';
  toolbar[toolbar.length] = { "type":"picker",
                    "title": "Alignment",
                    "icon" : "../../plugins/divalign2/images/pleft.png",
                    "key"  : "a",
                    "list" : align_da2_arr,
                    "icobase" : "../plugins/divalign2/images"};
  }

/* end of divalign2/script.js */
