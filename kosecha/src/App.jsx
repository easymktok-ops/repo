import Nav from './components/Nav.jsx';
import Hero from './components/Hero.jsx';
import MenuCompleto from './components/MenuCompleto.jsx';
import QuienesSomos from './components/QuienesSomos.jsx';
import Trazabilidad from './components/Trazabilidad.jsx';
import Ubicacion from './components/Ubicacion.jsx';
import Footer from './components/Footer.jsx';

export default function App() {
  return (
    <>
      <Nav />
      <main>
        <Hero />
        <MenuCompleto />
        <QuienesSomos />
        <Trazabilidad />
        <Ubicacion />
      </main>
      <Footer />
    </>
  );
}
