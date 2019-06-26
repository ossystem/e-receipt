
function onFixal(){
    var sumNal = parseFloat($("#sumNal").val()) || 0,
        sumCard = parseFloat($("#sumCard").val()) || 0,
        total = parseFloat($("#sumTotal").text()) || 0;

        $("#sumNal").val(sumNal);
        $("#sumCard").val(sumCard);
        console.log(sumNal + sumCard, total);

    return sumNal + sumCard == total;
}