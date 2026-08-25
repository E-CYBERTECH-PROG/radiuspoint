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

    let resolution_query = await fetch(`${rpCaptiveBase}/lookup-receipt`, {
        method: "POST",
        headers: { "Content-Type": "application/json", "Accept": "application/json" },
        body: JSON.stringify({ message: mpesa_code })
    });

    let response = await resolution_query.json();
    console.log(response);

    if (resolution_query.status == 200 && response.found) {
        resolve_container.remove();

        let username = response.username;
        let password = response.password;
        let login_via_http = `http://${hostname}/login?username=${username}&password=${password}&dst=https://youtube.com&popup=true`;

        Swal.fire({
            title: 'Success',
            text: 'Payment found — you are ready to connect.',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: `<a href="${login_via_http}" style="color: inherit; text-decoration: none;">Sign In</a>`,
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'custom-confirm-button'
            },
            didOpen: () => {
                const confirmButton = Swal.getConfirmButton();
                confirmButton.innerHTML = `<a href="${login_via_http}" style="color: inherit; text-decoration: none;">Sign In</a>`,
                    confirmButton.onclick = function () {
                        window.location.href = login_via_http;
                    }
            }
        });

    } else {
        resolve_container.remove();
        Swal.fire("Failed!", response.message || "No matching payment found.", "error");
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

        let submit_checkout = await fetch(`${rpPortalBase}/pay`, {
            method: "POST",
            headers: { "Content-Type": "application/json", "Accept": "application/json" },
            body: JSON.stringify({ plan_id: productid, phone: phone, mac: device_mac_address })
        });

        let response_body = await submit_checkout.json();
        console.log(response_body);

        if (submit_checkout.status == 200 && response_body.transaction_id) {
            let transaction_id = response_body.transaction_id;

            Swal.update({
                icon: 'info',
                title: 'Processing...',
                text: `Payment request sent to ${phone}, confirm and accept.`,
                showConfirmButton: false,
                allowOutsideClick: true,
            });
            Swal.showLoading();

            let checkout_query_interval = setInterval(async () => {
                let order_status_query = await fetch(`${rpPortalBase}/status/${transaction_id}`, {
                    headers: { "Accept": "application/json" }
                });

                if (order_status_query.status == 200) {
                    let result_body = await order_status_query.json();
                    console.log(result_body);

                    if (result_body.status === 'success') {
                        clearInterval(checkout_query_interval);
                        Swal.hideLoading();

                        let username = result_body.username;
                        let password = result_body.password;

                        Swal.fire({
                            title: `Payment confirmed`,
                            text: 'Click Sign In to continue',
                            icon: 'success',
                            showCancelButton: true,
                            cancelButtonText: 'Cancel',
                            showConfirmButton: true,
                            confirmButtonText: 'Sign In',
                            allowOutsideClick: true,
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = `http://${hostname}/login?username=${username}&password=${password}&dst=https://youtube.com&popup=true`;
                            }
                        });

                        document.sendin.username.value = username;
                        document.sendin.password.value = password;
                        document.sendin.submit();

                    } else if (result_body.status === 'pending') {
                        Swal.update({
                            icon: 'info',
                            title: `Pending ...`,
                            text: `Payment request sent to ${phone}, confirm and accept.`,
                            showConfirmButton: false,
                        });
                        Swal.showLoading();

                    } else {
                        // 'failed', or anything unexpected
                        clearInterval(checkout_query_interval);
                        Swal.hideLoading();
                        Swal.update({
                            icon: 'error',
                            title: 'Failed...',
                            text: 'The payment was not completed. Try again or contact support.',
                            showConfirmButton: false,
                            showCancelButton: true,
                            cancelButtonText: 'Cancel',
                            allowOutsideClick: true,
                        });
                    }
                } else {
                    clearInterval(checkout_query_interval);
                    Swal.hideLoading();
                    Swal.update({
                        icon: 'error',
                        title: 'Failed...',
                        text: `Could not check payment status, try again or contact support [${order_status_query.status}]`,
                        showConfirmButton: false,
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        allowOutsideClick: true,
                    });
                }
            }, 3000);

        } else {
            Swal.hideLoading();
            Swal.update({
                icon: 'error',
                title: 'Failed...',
                text: response_body.error || `Payment request to ${phone} failed, try again or contact support [${submit_checkout.status}]`,
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                allowOutsideClick: true,
            });
        }



    } else {
        // User clicked Cancel or closed the dialog
        console.log("Payment cancelled");
        Swal.close();
    }
}