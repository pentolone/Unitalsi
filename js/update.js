String.prototype.reverse = function () {
        return this.split("").reverse().join("");
    }

    function reformatText(prezzo) {        
        var x = prezzo.value;
        x = x.replace(/,/g, ""); // Strip out all commas
        x = x.reverse();
        x = x.replace(/.../g, function (e) {
            return e + ",";
        }); // Insert new commas
        x = x.reverse();
        x = x.replace(/^,/, ""); // Remove leading comma
        input.value = x;
    }