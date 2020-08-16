/**
 * @file    divalign2/script.js
 * @brief   Adds alignment picker to edit toolbar in Divalign2 plugin.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Luis Machuca Bezzaza <luis [dot] machuca [at] gulix [dot] cl>
 * @JS rewrited by Andrey Shpak <ashpak [at] ashpak [dot] ru> 
 */

 if(toolbar){ 
    toolbar[toolbar.length] = {"type":"format", "title":"Left", "key":"", 
                               "icon":"../../plugins/divalign2/images/pleft.png", 
                               "open":"#;;\n", "close":"\n#;;\n"
                              }; 
    toolbar[toolbar.length] = {"type":"format", "title":"Center", "key":"", 
                               "icon":"../../plugins/divalign2/images/pcenter.png", 
                               "open":";#;\n", "close":"\n;#;\n"
                              }; 
    toolbar[toolbar.length] = {"type":"format", "title":"Right", "key":"", 
                               "icon":"../../plugins/divalign2/images/pright.png", 
                               "open":";;#\n", "close":"\n;;#\n"
                              }; 
    toolbar[toolbar.length] = {"type":"format", "title":"Justify", "key":"", 
                               "icon":"../../plugins/divalign2/images/pjustify.png", 
                               "open":"###\n", "close":"\n###\n"
                              }; 
}
 
/* end of divalign2/script.js */
