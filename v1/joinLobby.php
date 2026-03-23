<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="lobby">

<!--Burger Menu-->
<div class="menu">
    <div class="burger">&#9776;</div>
    <div class="menu-content">
        <a href="index.php">Leave</a>
        <a href="#">Impressum</a>
        <a href="#">More</a>
    </div>
</div>

<!-- Main Content -->
<div class="container">

    <div class="logo-wrapper">
        <img src="img/logo.png" class="logo" alt="Logo">
        <svg class="wave-ring" viewBox="0 0 240 240">  <!-- Increased from 200x200 -->
        <path id="wavePath" fill="none" stroke="var(--highlight-color)" stroke-width="4"/>
    </svg>
    </div>

    <h1>Join Lobby</h1>

    <form action="joinLobby.php" method="post" class="lobby-form">

        <div class="join-row">
            <input type="text" name="lobbyCode" placeholder="Enter Lobby Code">

            <button type="submit">Join</button>
        </div>

    </form>

    <form action="lobby.php" method="post">
        <button type="submit">Create Lobby</button>
    </form>

</div>

<script>
(function() {
    const path = document.getElementById("wavePath");
    if (!path) return;  
    const center = 120
    const baseRadius = 105 
    const amplitude = 12   
    const waves = 12
    const points = 180

    let t = 0;
    let rotation = 0;
    const rotationSpeed = 0.01;

    function drawWave() {
        let d = "";
        const pulse = Math.sin(t);         

        for (let i = 0; i <= points; i++) {
            const angle = (i / points) * Math.PI * 2;
            const r = baseRadius + amplitude * pulse * Math.sin(waves * angle);

            const x = center + r * Math.cos(angle + rotation);
            const y = center + r * Math.sin(angle + rotation);

            if (i === 0) {
                d += `M ${x} ${y}`;
            } else {
                d += ` L ${x} ${y}`;
            }
        }
        d += " Z";   

        path.setAttribute("d", d);

        t += 0.05;
        rotation += rotationSpeed;

        requestAnimationFrame(drawWave);
    }

    drawWave();

    const path2 = document.getElementById("wavePath"); 
    let time = 0;

    function drawWaveCSSrotated() {
        let d = "";
        const pulse = Math.sin(time);   

        for (let i = 0; i <= points; i++) {
            const angle = (i / points) * Math.PI * 2;
            const r = baseRadius + amplitude * pulse * Math.sin(waves * angle);

            const x = center + r * Math.cos(angle);
            const y = center + r * Math.sin(angle);

            if (i === 0) d += `M ${x} ${y}`;
            else d += ` L ${x} ${y}`;
        }
        d += " Z";
        path2.setAttribute("d", d);

        time += 0.05;  // speed of pulsing
        requestAnimationFrame(drawWaveCSSrotated);
    }

    drawWaveCSSrotated();

    path2.setAttribute('stroke', 'var(--highlight-color)');
    path2.setAttribute('stroke-width', '4');
    path2.setAttribute('fill', 'none');
})();
</script>

</body>
</html>