<?php
     require_once('../../config.php');
  
     $search = '/(\$@FILE@\$)
                        (
                        (?:(?:\/|%2f|%2F))
                        (?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*
                        )?
                        (\?(?:(?:(?:\([-;:@#&=\pL0-9\$~_.+!*\',]*?\))|[-;:@#&=?\pL0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))
                        ?
                        (?<![,.;])/u';
 $search = '/(\$@FILE@\$)((?:(?:\/|%2f|%2F))(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*)?(\?(?:(?:(?:\([-;:@#&=a-zA-Z0-9\$~_.+!*\',]*?\))|[-;:@#&=?a-zA-Z0-9\$~_.+!*\',]|%[a-fA-F0-9]{2}|\/)*))?(?<![,.;])/';                   
                        
     $content = '"<p>What is the following picture ?</p>
     \r\n<p> </p>\r\n<p>
     <img src=\"\$@FILE@\$2/moddata/quiz/521/check.jpg\" /></p>\r\n<p> </p>\r\n<p> </p>\r\n<p> </p>\r\n<p>
     <img src=\"\$@FILE@\$2/moddata/quiz/521/ink.png\" /></p>"';
                
     
     $matches = array();
     
     preg_match_all($search,$content,$matches);
     print_object($matches);
?>
