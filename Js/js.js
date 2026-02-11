document.getElementById('regForm').onsubmit = function(e) {
            const pass = document.getElementById('u_pass').value;
            const conf = document.getElementById('u_conf').value;
            const alertBox = document.getElementById('js-alert');

            if (pass !== conf) {
                e.preventDefault(); 
                alertBox.innerText = "⚠️ عذراً.. كلمتا المرور غير متطابقتين!";
                alertBox.style.display = "block";
                
                
                document.getElementById('u_pass').style.border = "2px solid red";
                document.getElementById('u_conf').style.border = "2px solid red";
                return false;
            }
        };
