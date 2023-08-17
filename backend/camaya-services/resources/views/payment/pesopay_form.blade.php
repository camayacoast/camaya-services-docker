<form method="post" action="{{ $pesopay_endpoint }}" id="pesoPayForm">
    <input type="hidden" name="merchantId" value="{{ $merchantId }}" />
    <input type="hidden" name="orderRef" value="{{ $generateTransactionID }}" />
    <input type="hidden" name="currCode" value="608" />
    <input type="hidden" name="amount" value="{{ $amount }}" />
    <input type="hidden" name="lang" value="E" />
    <input type="hidden" name="cancelUrl" value="{{ $cancelUrl }}" />
    <input type="hidden" name="failUrl" value="{{ $failUrl }}" />
    <input type="hidden" name="successUrl" value="{{ $successUrl }}" />
    <input type="hidden" name="payType" value="N" />
    <input type="hidden" name="payMethod" value="ALL" />
    <input type="hidden" name="secureHash" value="{{ $secureHash }}" />
</form>
<script>
    document.getElementById("pesoPayForm").submit();
</script>