<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sine Wave Logo</title>

<style>

body{
    display:flex;
    justify-content:center;
    align-items:center;
    height:100vh;
    background:#111;
}

.logo-wrapper{
    position:relative;
    width:150px;
    height:150px;
}

.logo{
    width:100%;
    border-radius:50%;
    position:relative;
    z-index:2;
}

.wave-ring{
    position:absolute;
    top:50%;
    left:50%;
    width:250px;
    height:250px;
    transform:translate(-50%,-50%);
    z-index:1;
}

.wave-ring path{
    fill:none;
    stroke:#6ee7ff;
    stroke-width:3;
}

</style>
</head>
<body>

<div class="logo-wrapper">

<img src="img/logoLight.png" class="logo">

<svg class="wave-ring" viewBox="0 0 200 200">
    <path id="wavePath"></path>
</svg>

</div>

<script>
const path = document.getElementById("wavePath")

const center = 100
const baseRadius = 85
const amplitude = 8
const waves = 12
const points = 180

let t = 0
let rotation = 0
const rotationSpeed = 0.01 // adjust speed

function drawWave(){
    let d = ""
    const pulse = Math.sin(t)

    for(let i=0; i <= points; i++){
        const angle = (i/points) * Math.PI * 2
        const r = baseRadius + amplitude * pulse * Math.sin(waves * angle)

        // Apply rotation only to final x/y coordinates
        const x = center + r * Math.cos(angle + rotation)
        const y = center + r * Math.sin(angle + rotation)

        if(i === 0){
            d += `M ${x} ${y}`
        } else {
            d += ` L ${x} ${y}`
        }
    }

    d += " Z"
    path.setAttribute("d", d)

    t += 0.05
    rotation += rotationSpeed

    requestAnimationFrame(drawWave)
}
drawWave()


</script>

</body>
</html>