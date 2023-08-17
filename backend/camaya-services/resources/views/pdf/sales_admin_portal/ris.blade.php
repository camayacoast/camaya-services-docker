<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style>
@page {
/* size: 21cm 29.7cm; */
size: 215.9mm 355.6mm;
margin: 0mm 0mm 0mm 0mm;
/* change the margins as you want them to be. */
}
@media print {
  html, body {
    /* width: 215.9mm; */
    /* height: 355.6mm; */
  }
  /* ... the rest of the rules ... */
}

</style>
</head>
<body>

<div class="wrapper">
@include('pdf.sales_admin_portal.components.ra')

@include('pdf.sales_admin_portal.components.bis')
</div>

</body>
</html>