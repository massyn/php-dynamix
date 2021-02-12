<?php
class CORE {

   function flattenSchema($q) {

   $r = array();
   foreach ($q as $f => $d) {
      
      $S = array();

      $value = '';    // TODO
      if(is_array($d)) {
         $field = $f;

         // -- desc
         if (isset($d['desc'])) {
               $S['desc'] = $d['desc'];
         } else {
               $S['desc'] = $f;
         }
         // -- required
         if (isset($d['required'])) {
               $S['required'] = TRUE;
         } else {
               $S['required'] = FALSE;
         }
         // -- helptext
         if (isset($d['helptext'])) {
               $S['helptext'] = $d['helptext'];
         } else {
               $S['helptext'] = '';
         }
         // -- type
         if (isset($d['type'])) {
               $S['type'] = $d['type'];
         } else {
               $S['type'] = 'text';
         }

      } else {
         $field = $d;
         $S['desc'] = $d;
         $S['required'] = FALSE;
         $S['helptext'] = '';
         $S['type'] = 'text';
      }
      $r[$field] = $S;
      
   }
   return $r;
   }
}
?>