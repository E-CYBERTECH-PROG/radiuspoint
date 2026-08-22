
/**
* modified on 07 - 12 - 2025 09:23:19
* DO NOT MODIFY ANYTHING ON THIS PAGE
* BILLNASI HOTSPOT VERSION(1) DEC 2020
* BILLNASI HOTSPOT VERSION(2) NOV 2024
* BILLNASI HOTSPOT VERSION(3) MAR 2025
* BILLNASI HOTSPOT VERSION(4) APR 2025
* CODE CLEANUP, REVEIW AND OPTIMISATION DEC 2025
*/
//simpler js all complexities removed

function remove_info_dialog() {
    let info_dialog = document.querySelector(".info");
    if (info_dialog) {
        info_dialog.classList.add("hide");
    }
}
setTimeout(remove_info_dialog, 10000);

document.querySelectorAll("input").forEach(input => {

    input.addEventListener("click", remove_info_dialog);

});

const form_template = new FormData();
form_template.append("patner", patner);
form_template.append("apikey", apikey);
form_template.append("nas", nasname);
form_template.append("ip", device_ip_address);
form_template.append("mac", device_mac_address); //mac of this device
form_template.append("account", device_mac_address); //mac of this device
form_template.append("hostname", hostname);
form_template.append("servername", host_server_name); //servername of this hotspot



const resolve_container = document.querySelector(".resolve-payment");


function trim_resolve_input() {
    let resolve_input = document.querySelector("#mpesa-code:valid");
    //  console.log(resolve_input);

    if (resolve_input != null) {
        document.getElementById("search-api-button").disabled = false;
        let trimmed_input = resolve_input.value.substring(0, 10);
        resolve_input.value = trimmed_input;
        // console.log(trimmed_input);

        if (trimmed_input.length == 10) {
            console.log("Trying to login via http");
           let try_to_login = fetch(`http://${hostname}/login?username=${trimmed_input}&password=${trimmed_input}&dst=https://youtube.com&popup=true`);
        }

    } else {
        document.getElementById("search-api-button").disabled = true;
        //   console.log("Input is invalid");

    }
}



// this consts have been declared here for relevance

const resolve_trx_button = document.querySelector("#search-api-button");
const mpesa_code_input = document.querySelector("#mpesa-code");
resolve_trx_button.addEventListener("click", resolve_transaction);
//search-api-button
//mpesa-code
async function resolve_transaction() {

    let mpesa_code = mpesa_code_input.value;
    var pattern = /^[A-Z0-9]+$/;
    mpesa_code = mpesa_code.substring(0, 10);
    if (mpesa_code == '' && pattern.test(mpesa_code)) {
        alert("M-PESA Transaction Code is Required");
        Swal.fire("Failed!", "M-PESA Transaction Code is Required", "error");
        return;
    }
    resolve_container.innerHTML = "<i>Please wait...</i>";
    let resolve_form = new FormData();
    resolve_form.append("patner", patner);
    resolve_form.append("apikey", apikey);
    resolve_form.append("nas", nasname);
    resolve_form.append("ip", server_variables["ip-address"]);
    resolve_form.append("mac", server_variables["mac-address"]);
    resolve_form.append("servicetype", "hotspot");
    resolve_form.append("TrxCode", mpesa_code);

    let resolution_query = await fetch(`https://${destination_instance}/endpoints/hs_resolve.php`,
        {
            method: "POST",
            body: resolve_form
        }
    )
    if (resolution_query.status == 200) {

        resolve_container.remove();
        /*
      {"response_type":"error","message":"Transaction does not exist"}
      */

        let response = await resolution_query.json();
        console.log(response);

        let ResponseCode = await response.ResponseCode;
         let ResponseDesc = await response.ResponseDesc;
        let Remarks = await response.Remarks ?? "No remarks";
        console.log(Remarks);


        if (ResponseCode == 0) {
            let username = await response.username;
            let password = await response.password;
            let login_via_http = `http://${hostname}/login?username=${username}&password=${password}&dst=https://youtube.com&popup=true`;

            Swal.fire({
                title: 'Success',
                text: ResponseDesc,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: `<a href="${login_via_http}" style="color: inherit; text-decoration: none;">Sign In</a>`,
                cancelButtonText: 'Cancel',
                customClass: {
                    confirmButton: 'custom-confirm-button'
                },
                didOpen: () => {
                    // Make the anchor tag clickable
                    const confirmButton = Swal.getConfirmButton();
                    confirmButton.innerHTML = `<a href="${login_via_http}" style="color: inherit; text-decoration: none;">Sign In</a>`,
                        confirmButton.onclick = function () {
                            window.location.href = login_via_http;
                        }
                }
            });


        } else {
            let ResponseDesc = await response.ResponseDesc;
            Swal.fire("Failed!", "Error " + ResponseDesc, "error");
        }



    } else {
        //curl error
        resolve_container.remove();

        Swal.fire("Failed!", "Curl error, request not found", "error");
    }
}


let year = new Date();
year = year.getFullYear();
document.getElementById("show-year").innerText = year;


var user_phonenumber = "";


async function show_checkout_modal(package_amount, currency, package_name, productid) {
    if(user_phonenumber == undefined){
        user_phonenumber = "";
    }
    console.log(user_phonenumber);
    const { value: phone } = await Swal.fire({
        //  title: `Purchase ${package_name}`,
        html: `
            <div style="text-align: left; margin-bottom: 2px;">
                <strong>Package:</strong> ${package_name}<br>
                <strong>Amount:</strong> ${currency} ${package_amount}
            </div>
            <hr>
            <p style="margin: 2px auto;">Please enter your phone number to continue:</p>
        `,
        input: 'text',
        inputValue: user_phonenumber,
        inputLabel: 'Phone Number',
        inputPlaceholder: 'e.g. 07...',
        inputAttributes: {
            autocapitalize: 'off',
            autocorrect: 'off'
        },
        showCancelButton: true,
        confirmButtonText: 'Submit',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        allowOutsideClick: true,
        preConfirm: () => {
            const inputValue = Swal.getInput().value.trim();

            // Basic phone validation (you can make this stricter)
            if (!inputValue) {
                Swal.showValidationMessage('Phone number is required');
                return false;
            }
            if (inputValue.length < 10) {
                Swal.showValidationMessage('Please enter a valid phone number');
                return false;
            }
            return inputValue;
        }
    });

    // This runs only if user clicked "Submit" and validation passed
    if (phone) {
        user_phonenumber = phone;
        // Here you can proceed with payment (e.g. M-Pesa STK push, etc.)
        console.log('Phone:', phone);
        console.log('Package:', package_name);
        console.log('Amount:', currency, package_amount);
        console.log('Product ID:', productid);

        // Example: send to your backend
        // await fetch('/pay', { method: 'POST', body: JSON.stringify({phone, productid, ...}) });

        Swal.fire({
            icon: 'info',
            title: 'Processing...',
            text: `Sending payment request to ${phone}...`,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading(); // This adds the official SweetAlert2 spinner
            }
        });

        form_template.append("phonenumber", phone);
        //checkout_request_body.append("amount", amount);
        //checkout_request_body.append("email", email); 
        form_template.append("productid", productid);


        let submit_checkout = await fetch(`https://${destination_instance}/checkout/hotspot.php`, {
            method: "POST",
            body: form_template
        });

        if (submit_checkout.status == 200) {//if we have gotten a reply

            let response_body = await submit_checkout.json();

            console.log(response_body);
            let ResponseCode = response_body.ResponseCode ?? 500;// 0 for checkout, 10 for recover
            let ResponseDescription = response_body.ResponseDescription;
            let ResponseType = response_body.ResponseType;
            let CheckoutRequestID = response_body.CheckoutRequestID;
            let Remarks = response_body.Remarks ?? "Please wait ....";

            let RedirectUrl = response_body.RedirectUrl;

            if (ResponseCode == 0) {
                //Swal.showLoading(); // This adds the official SweetAlert2 spinner
                Swal.update({
                    icon: 'info',
                    title: 'Processing...',
                    text: `${Remarks}`,
                    showConfirmButton: false,
                    allowOutsideClick: true,
                });

                Swal.showLoading();


                if (RedirectUrl != null) {

                    window.open(RedirectUrl, "_self");

                }

                let checkout_query_interval = setInterval(async () => {//enquire the server on the status of this order every  seconds
                    let order_status_query = await fetch(`https://${destination_instance}/checkout/status.php?CheckoutRequestID=${CheckoutRequestID}`);

                    if (order_status_query.status == 200) {
                        /*
                        {
            "RequestBody": {
                "CheckoutRequestID": "ws_CO_17112024003710569704343***"
            },
            "ResultCode": "0",
            "Remarks": "Request processed successfully, reload page now. TransactionID = SKH539WIS5",
            "Initiator": "Express",
            "TrxCode": "SKH539WIS5"
           }*/
                        let result_body = await order_status_query.json();
                        console.log(result_body);

                        let ResultCode = result_body.ResultCode;
                        let TrxCode = result_body["TrxCode"];
                        Remarks = result_body.Remarks;


                        if (ResultCode == 0) {
                            //success
                            clearInterval(checkout_query_interval);    // stop interval
                            Swal.hideLoading();
                            Swal.fire({
                                title: `Payment confirmed ${TrxCode}`,
                                text: 'Click Sign In to continue',
                                icon: 'success',
                                showCancelButton: true,
                                cancelButtonText: 'Cancel',
                                showConfirmButton: true,
                                confirmButtonText: 'Sign In',          // plain text (keeps default styling)
                                allowOutsideClick: true,
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Redirect when the styled button is clicked
                                    window.location.href = `http://${hostname}/login?username=${TrxCode}&password=${TrxCode}&dst=https://youtube.com&popup=true`;
                                    // or window.open('https://your-signin-page.com', '_blank');
                                }
                            });


                            document.sendin.username.value = TrxCode;
                            document.sendin.password.value = TrxCode;
                            document.sendin.submit();


                        } else if (ResultCode == null) {
                            //pending
                            Swal.update({
                                icon: 'info',
                                title: `Pending ...`,
                                text: `Payment request sent to ${phone}, confirm and accept.`,
                                showConfirmButton: false,
                            });
                            Swal.showLoading();

                        } else if (ResultCode > 0) {//            
                            //failed
                            clearInterval(checkout_query_interval);// stop interval
                            
                            //console.log(Remarks);
                            if (typeof Remarks != "string") {
                                Remarks = "Payment request failed, try again or contact support";
                            }
                            //console.log(Remarks);

                            Swal.hideLoading();
                            Swal.update({
                                icon: 'error',
                                title: 'Failed...',
                                text: Remarks,
                                showConfirmButton: false,
                                showCancelButton: true

                            });
                        } else {
                            //resultcode somthing else other than 0
                            //not null
                            //not zero
                            //not greater than 0
                            clearInterval(checkout_query_interval);// stop interval


                            Swal.hideLoading();
                            Swal.update({
                                icon: 'error',
                                title: 'Failed...',
                                text: `Failed to get payment request status, try again or contact support [${order_status_query.status}]`,
                                showConfirmButton: false,
                                showCancelButton: true,
                                cancelButtonText: 'Cancel',         // plain text (keeps default styling)
                                allowOutsideClick: true,

                            });
                        }
                    } else {
                        //check order status returned a http code diff from 200
                        clearInterval(checkout_query_interval);// stop interval
                        Swal.hideLoading();

                        if (typeof Remarks != "string") {
                            Remarks = "Payment request failed (checkout stage 1), try again or contact support";
                        }

                        Swal.update({
                            icon: 'error',
                            title: 'Failed...',
                            text: `${Remarks} [${order_status_query.status}]`,
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Cancel',         // plain text (keeps default styling)
                            allowOutsideClick: true,

                        });
                    }
                }, 3000);//query status of payment request every 3 seconds



            } else if (ResponseCode == 10) {//for recovered vouchers


                let recovered_items = response_body.items;
                console.log("VOUCHER RECOVERY ON CHECKOUT: ", response_body);

                item = recovered_items;

                let item_username = item.username;
                let item_amount = item.amount;
                let item_date = item.TrxDate;

                Swal.hideLoading();

                Swal.fire({
                    title: `1 UNUSED VOUCHER ${item_username}`,
                    text: 'Click Sign In to continue',
                    icon: 'warning',
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    confirmButtonText: 'Sign In',          // plain text (keeps default styling)
                    allowOutsideClick: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect when the styled button is clicked
                        window.location.href = `http://${hostname}/login?username=${item_username}&password=${item_username}&dst=https://youtube.com&popup=true`;
                        // or window.open('https://your-signin-page.com', '_blank');
                    }
                });

            } else {
                //.hotspot.php returned something that is not = 10
                //not is in 0
                Swal.hideLoading();
                if (typeof Remarks != "string") {
                    Remarks = "Payment request failed (checkout stage 1), try again or contact support";
                }


                Swal.update({
                    icon: 'error',
                    title: 'Failed...',
                    text: `${Remarks} [${submit_checkout.status}]`,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',         // plain text (keeps default styling)
                    allowOutsideClick: true,

                });
            }

        } else {
            //http info for hotspot.php
            //is not 200
            Swal.hideLoading();

            Swal.update({
                icon: 'error',
                title: 'Failed...',
                text: `Payment request to ${phone} failed, try again or contact support [${submit_checkout.status}]`,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',         // plain text (keeps default styling)
                allowOutsideClick: true,

            });
        }



    } else {
        // User clicked Cancel or closed the dialog
        console.log("Payment cancelled");
        Swal.close();
    }
}