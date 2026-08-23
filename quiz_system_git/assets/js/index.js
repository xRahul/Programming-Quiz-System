/**
 * index.js - roll-no validation + right-click guard, extracted from
 * index.php so the page ships without script-src unsafe-inline (T4.5).
 */
            function submit(){
                var x=document.forms["onlyForm"]["rollno"].value;
                if (x==null || x==""){
                    document.getElementById("enter_rollno").innerHTML = "Please Enter Your Roll No.";
                    return false;
                }
                document.getElementById('myForm').submit();
                return false;
            }

            document.addEventListener("contextmenu", function(e){
                e.preventDefault();
            }, false);
