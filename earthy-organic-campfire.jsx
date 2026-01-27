import React, { useState } from 'react';

const EarthyOrganicCampfire = () => {
  const [isPlaying, setIsPlaying] = useState(true);
  const [expandedTrack, setExpandedTrack] = useState(1);
  const [selectedRecording, setSelectedRecording] = useState(1);
  
  const tracks = [
    { id: 0, title: 'Truckin\'', versions: 847, duration: '5:08' },
    { id: 1, title: 'Dark Star', versions: 232, duration: '23:18' },
    { id: 2, title: 'Sugar Magnolia', versions: 595, duration: '8:45' },
    { id: 3, title: 'Friend of the Devil', versions: 432, duration: '7:22' },
    { id: 4, title: 'Casey Jones', versions: 312, duration: '6:55' },
  ];
  
  const recordings = [
    { year: '1977', venue: 'Barton Hall', location: 'Cornell University', date: '05/08/77', rating: 847, length: '23:18', taper: 'Betty Cantor-Jackson' },
    { year: '1972', venue: 'Veneta, OR', location: 'Old Renaissance Faire', date: '08/27/72', rating: 523, length: '31:42', taper: 'Owsley Stanley' },
    { year: '1974', venue: 'Winterland', location: 'San Francisco, CA', date: '10/18/74', rating: 289, length: '26:15', taper: 'unknown' },
  ];
  
  return (
    <div style={{
      minHeight: '100vh',
      background: '#1c1a17',
      fontFamily: 'Georgia, serif',
      color: '#e8e0d4',
      position: 'relative',
      overflow: 'hidden'
    }}>
      
      <style>{`
        @keyframes spinLeft {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        @keyframes spinRight {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
        @keyframes flicker {
          0%, 100% { opacity: 0.2; }
          50% { opacity: 0.35; }
        }
        @keyframes firefly {
          0%, 100% { opacity: 0; transform: scale(0.5); }
          50% { opacity: 0.5; transform: scale(1); }
        }
        .reel-left { animation: spinLeft 2s linear infinite; animation-play-state: ${isPlaying ? 'running' : 'paused'}; }
        .reel-right { animation: spinRight 1.2s linear infinite; animation-play-state: ${isPlaying ? 'running' : 'paused'}; }
        .fire-glow { animation: flicker 3s ease-in-out infinite; }
        .firefly { animation: firefly 4s ease-in-out infinite; }
        .firefly-2 { animation: firefly 5s ease-in-out infinite 1.5s; }
        .firefly-3 { animation: firefly 3.5s ease-in-out infinite 0.8s; }
        .track-row:hover { background: rgba(212,160,96,0.04); }
        .recording-card { transition: all 0.2s ease; }
        .recording-card:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(0,0,0,0.3); }
      `}</style>
      
      {/* Organic blob background */}
      <div style={{
        position: 'fixed',
        inset: 0,
        background: `
          radial-gradient(ellipse 600px 500px at 10% 20%, rgba(90,110,85,0.07) 0%, transparent 70%),
          radial-gradient(ellipse 500px 600px at 85% 60%, rgba(180,120,90,0.05) 0%, transparent 70%),
          radial-gradient(ellipse 700px 400px at 50% 90%, rgba(140,155,130,0.05) 0%, transparent 70%),
          radial-gradient(ellipse 400px 400px at 40% 50%, rgba(160,120,115,0.03) 0%, transparent 70%)
        `,
        pointerEvents: 'none'
      }}/>
      
      {/* Ambient fire glow at bottom */}
      <div className="fire-glow" style={{
        position: 'fixed',
        bottom: -100,
        left: '50%',
        transform: 'translateX(-50%)',
        width: '120%',
        height: 400,
        background: 'radial-gradient(ellipse at 50% 100%, rgba(200,120,50,0.12) 0%, rgba(180,100,40,0.04) 40%, transparent 70%)',
        pointerEvents: 'none'
      }}/>
      
      {/* Fireflies */}
      <div className="firefly" style={{ position: 'fixed', top: '25%', left: '12%', width: 5, height: 5, borderRadius: '50%', background: '#d4a060', boxShadow: '0 0 12px rgba(212,160,96,0.6)' }}/>
      <div className="firefly-2" style={{ position: 'fixed', top: '55%', left: '82%', width: 4, height: 4, borderRadius: '50%', background: '#d4a060', boxShadow: '0 0 10px rgba(212,160,96,0.5)' }}/>
      <div className="firefly-3" style={{ position: 'fixed', top: '70%', left: '25%', width: 4, height: 4, borderRadius: '50%', background: '#c08a40', boxShadow: '0 0 8px rgba(192,138,64,0.5)' }}/>
      
      {/* Top nav */}
      <div style={{
        display: 'flex',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: '18px 36px',
        borderBottom: '1px solid rgba(200,180,150,0.08)',
        position: 'relative',
        zIndex: 10,
        background: 'rgba(28,26,23,0.6)',
        backdropFilter: 'blur(10px)'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 24 }}>
          <div style={{ color: '#d4a060', fontSize: 22, display: 'flex', alignItems: 'center', gap: 10 }}>
            <span style={{ fontSize: 24 }}>⚡</span>
            <span style={{ fontWeight: 400 }}>Campfire Tapes</span>
          </div>
          <div style={{ color: '#6a6458', fontSize: 13 }}>
            <span style={{ color: '#8a8478', marginRight: 8, cursor: 'pointer' }}>←</span>
            Grateful Dead
          </div>
        </div>
        
        <div style={{ display: 'flex', gap: 28, fontSize: 14, color: '#8a8478', fontFamily: 'system-ui' }}>
          <span style={{ cursor: 'pointer' }}>Home</span>
          <span style={{ cursor: 'pointer' }}>Search</span>
          <span style={{ cursor: 'pointer', color: '#e8e0d4' }}>Your Tapes</span>
          <span style={{ cursor: 'pointer', color: '#d4a060' }}>☮</span>
        </div>
      </div>
      
      {/* Main content */}
      <div style={{ maxWidth: 1000, margin: '0 auto', padding: '48px 36px 140px', position: 'relative', zIndex: 5 }}>
        
        {/* Album header */}
        <div style={{ textAlign: 'center', marginBottom: 20 }}>
          <div style={{ 
            color: '#6a6458', 
            fontSize: 11, 
            letterSpacing: 4, 
            marginBottom: 8,
            fontFamily: 'system-ui'
          }}>
            ✦ LIVE FROM THE VAULT ✦
          </div>
        </div>
        
        {/* Hero section */}
        <div style={{ display: 'flex', gap: 56, marginBottom: 56, alignItems: 'flex-start', justifyContent: 'center' }}>
          
          {/* Cassette */}
          <div style={{ position: 'relative', flexShrink: 0 }}>
            
            {/* Decorative rose */}
            <div style={{
              position: 'absolute',
              top: -12,
              right: -16,
              fontSize: 24,
              transform: 'rotate(12deg)',
              opacity: 0.6
            }}>🌹</div>
            
            <div style={{
              width: 340,
              height: 220,
              background: 'linear-gradient(180deg, #383530 0%, #2a2725 50%, #1e1c1a 100%)',
              borderRadius: 12,
              position: 'relative',
              boxShadow: '0 20px 60px rgba(0,0,0,0.4), 0 8px 24px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.04)',
              transform: 'rotate(-1deg)'
            }}>
              {/* Screws */}
              {[[16,16],[324,16],[16,204],[324,204]].map(([x,y],i) => (
                <div key={i} style={{
                  position: 'absolute', left: x-6, top: y-6,
                  width: 12, height: 12, borderRadius: '50%',
                  background: 'radial-gradient(circle at 35% 35%, #4a4540, #151412)'
                }}>
                  <div style={{ position:'absolute', top:'50%', left:'50%', transform:'translate(-50%,-50%)', width:6, height:1.5, background:'#151412' }}/>
                  <div style={{ position:'absolute', top:'50%', left:'50%', transform:'translate(-50%,-50%) rotate(90deg)', width:6, height:1.5, background:'#151412' }}/>
                </div>
              ))}
              
              {/* Label - natural cream paper */}
              <div style={{
                position: 'absolute',
                top: 14, left: 24, right: 24, height: 95,
                background: 'linear-gradient(180deg, #f5f0e8 0%, #ebe5d8 50%, #e0d8c8 100%)',
                borderRadius: 6,
                overflow: 'hidden',
                boxShadow: '0 2px 8px rgba(0,0,0,0.2), inset 0 1px 0 rgba(255,255,255,0.6)'
              }}>
                {/* Rust header band */}
                <div style={{
                  height: 22,
                  background: 'linear-gradient(180deg, #a85a38 0%, #8a4828 100%)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: '0 12px',
                  fontSize: 8,
                  fontWeight: 700,
                  color: '#f5f0e8',
                  letterSpacing: 2,
                  fontFamily: 'system-ui'
                }}>
                  <span>⚡ LIVE RECORDING ⚡</span>
                  <span style={{ opacity: 0.8, fontWeight: 400 }}>Type II XL 90</span>
                </div>
                
                {/* Label content */}
                <div style={{ padding: '8px 12px', position: 'relative' }}>
                  {/* Paper texture lines */}
                  <div style={{ position: 'absolute', top: 38, left: 12, right: 12, height: 1, background: 'rgba(120,100,70,0.08)' }}/>
                  <div style={{ position: 'absolute', top: 54, left: 12, right: 12, height: 1, background: 'rgba(120,100,70,0.06)' }}/>
                  
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div>
                      <div style={{ color: '#2a2420', fontSize: 18, fontWeight: 600, marginBottom: 2 }}>
                        Cornell '77 ☮
                      </div>
                      <div style={{ color: '#4a4035', fontSize: 11, fontStyle: 'italic' }}>
                        Grateful Dead — Barton Hall
                      </div>
                    </div>
                    <div style={{ color: '#7a6a5a', fontSize: 14, fontStyle: 'italic' }}>'77</div>
                  </div>
                </div>
                
                {/* Footer */}
                <div style={{
                  position: 'absolute',
                  bottom: 0, left: 0, right: 0,
                  height: 18,
                  background: 'rgba(0,0,0,0.03)',
                  borderTop: '1px solid rgba(120,100,70,0.08)',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  gap: 14,
                  fontSize: 8,
                  color: '#6a5a4a',
                  fontFamily: 'system-ui'
                }}>
                  <span>22 tracks</span>
                  <span style={{ color: '#a85a38' }}>✦</span>
                  <span>3:58:00</span>
                  <span style={{ color: '#a85a38' }}>✦</span>
                  <span>legendary</span>
                </div>
              </div>
              
              {/* Tape window */}
              <div style={{
                position: 'absolute',
                top: 118, left: 42, right: 42,
                height: 75,
                background: '#0c0b0a',
                borderRadius: 6,
                border: '2px solid #353230',
                overflow: 'hidden',
                boxShadow: 'inset 0 4px 14px rgba(0,0,0,0.7)'
              }}>
                {/* Glass shine */}
                <div style={{
                  position: 'absolute', top: 0, left: 0, right: 0, height: '40%',
                  background: 'linear-gradient(180deg, rgba(255,255,255,0.02), transparent)'
                }}/>
                
                {/* Left reel */}
                <div className="reel-left" style={{
                  position: 'absolute', left: 25, top: '50%', transform: 'translateY(-50%)',
                  width: 52, height: 52, borderRadius: '50%',
                  background: 'radial-gradient(circle at 40% 40%, #353230, #151412)',
                  border: '2px solid #353230'
                }}>
                  <div style={{ position: 'absolute', inset: 5, borderRadius: '50%', background: 'radial-gradient(circle, #5a4a3a, #3a2a1a)' }}/>
                  <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', width: 16, height: 16, borderRadius: '50%', background: '#151412', border: '2px solid #353230' }}>
                    {[0,60,120,180,240,300].map(d => (
                      <div key={d} style={{ position:'absolute', top:'50%', left:'50%', width:7, height:1.5, background:'#4a4540', transform:`translate(-50%,-50%) rotate(${d}deg)` }}/>
                    ))}
                  </div>
                </div>
                
                {/* Right reel */}
                <div className="reel-right" style={{
                  position: 'absolute', right: 25, top: '50%', transform: 'translateY(-50%)',
                  width: 52, height: 52, borderRadius: '50%',
                  background: 'radial-gradient(circle at 40% 40%, #353230, #151412)',
                  border: '2px solid #353230'
                }}>
                  <div style={{ position: 'absolute', inset: 14, borderRadius: '50%', background: 'radial-gradient(circle, #5a4a3a, #3a2a1a)' }}/>
                  <div style={{ position: 'absolute', top: '50%', left: '50%', transform: 'translate(-50%,-50%)', width: 16, height: 16, borderRadius: '50%', background: '#151412', border: '2px solid #353230' }}>
                    {[0,60,120,180,240,300].map(d => (
                      <div key={d} style={{ position:'absolute', top:'50%', left:'50%', width:7, height:1.5, background:'#4a4540', transform:`translate(-50%,-50%) rotate(${d}deg)` }}/>
                    ))}
                  </div>
                </div>
                
                {/* Tape */}
                <div style={{
                  position: 'absolute', top: '50%', left: 77, right: 77, height: 3,
                  transform: 'translateY(-50%)',
                  background: 'linear-gradient(180deg, #6a5a4a, #4a3a2a, #6a5a4a)'
                }}/>
              </div>
              
              {/* Side indicators */}
              <div style={{ position: 'absolute', bottom: 8, left: 28, fontSize: 8, color: '#5a5550', letterSpacing: 1, display: 'flex', alignItems: 'center', gap: 5, fontFamily: 'system-ui' }}>
                <div style={{ width: 7, height: 7, borderRadius: '50%', background: isPlaying ? '#d4a060' : '#4a4540', boxShadow: isPlaying ? '0 0 10px rgba(212,160,96,0.5)' : 'none', transition: 'all 0.3s' }}/>
                SIDE A
              </div>
              <div style={{ position: 'absolute', bottom: 8, right: 28, fontSize: 8, color: '#4a4540', letterSpacing: 1, display: 'flex', alignItems: 'center', gap: 5, fontFamily: 'system-ui' }}>
                SIDE B
                <div style={{ width: 7, height: 7, borderRadius: '50%', border: '1px solid #4a4540' }}/>
              </div>
            </div>
            
            {/* Fire glow */}
            <div className="fire-glow" style={{
              position: 'absolute', bottom: -25, left: '50%', transform: 'translateX(-50%)',
              width: 280, height: 70,
              background: 'radial-gradient(ellipse, rgba(220,140,60,0.3) 0%, rgba(180,100,40,0.15) 40%, transparent 70%)',
              filter: 'blur(18px)'
            }}/>
            
            {/* Dancing bear */}
            <div style={{
              position: 'absolute',
              bottom: -8,
              left: -20,
              fontSize: 22,
              opacity: 0.5,
              transform: 'rotate(-8deg)'
            }}>🐻</div>
          </div>
          
          {/* Album info */}
          <div style={{ paddingTop: 20, maxWidth: 380 }}>
            <div style={{ color: '#5a8a7a', fontSize: 10, letterSpacing: 3, marginBottom: 12, fontFamily: 'system-ui' }}>
              ☮ LIVE ALBUM
            </div>
            <div style={{ fontSize: 48, color: '#e8e0d4', marginBottom: 8, lineHeight: 1 }}>
              Cornell '77
            </div>
            <div style={{ fontSize: 18, color: '#a8a098', marginBottom: 6, fontStyle: 'italic' }}>
              Barton Hall
            </div>
            <div style={{ color: '#8a8478', fontSize: 14, marginBottom: 28, fontFamily: 'system-ui' }}>
              <span style={{ color: '#d4a060' }}>Grateful Dead</span> • May 8, 1977 • 22 tracks
            </div>
            
            <div style={{ 
              color: '#8a8478', 
              fontSize: 13, 
              fontStyle: 'italic', 
              marginBottom: 28,
              padding: '14px 18px',
              background: 'rgba(90,138,122,0.08)',
              borderRadius: 8,
              borderLeft: '3px solid #5a8a7a',
              lineHeight: 1.5
            }}>
              "The legendary show that defined an era. Please copy freely — never sell."
            </div>
            
            <div style={{ display: 'flex', gap: 14, alignItems: 'center' }}>
              <button 
                onClick={() => setIsPlaying(!isPlaying)}
                style={{
                  width: 56, height: 56, borderRadius: '50%',
                  background: 'linear-gradient(135deg, #d4a060, #b88030)',
                  border: 'none', cursor: 'pointer',
                  boxShadow: '0 4px 20px rgba(212,160,96,0.3)',
                  color: '#1c1a17', fontSize: 20,
                  display: 'flex', alignItems: 'center', justifyContent: 'center'
                }}
              >
                {isPlaying ? '❚❚' : '▶'}
              </button>
              <button style={{
                padding: '14px 24px',
                background: 'transparent',
                border: '1px solid #4a4640',
                borderRadius: 6,
                color: '#a8a098',
                fontSize: 13,
                cursor: 'pointer',
                fontFamily: 'system-ui'
              }}>⟲ Shuffle</button>
              <button style={{
                width: 44, height: 44, borderRadius: '50%',
                border: '1px solid #4a4640', background: 'transparent',
                color: '#8a8478', fontSize: 20, cursor: 'pointer',
                display: 'flex', alignItems: 'center', justifyContent: 'center'
              }}>♡</button>
            </div>
          </div>
        </div>
        
        {/* Side A divider */}
        <div style={{ 
          display: 'flex', 
          alignItems: 'center', 
          gap: 16, 
          marginBottom: 28 
        }}>
          <div style={{ flex: 1, height: 1, background: 'linear-gradient(90deg, transparent, rgba(200,180,150,0.2))' }}/>
          <div style={{ 
            color: '#8a8478', 
            fontSize: 11, 
            letterSpacing: 4,
            fontFamily: 'system-ui',
            display: 'flex',
            alignItems: 'center',
            gap: 10
          }}>
            <span style={{ color: '#d4a060' }}>✧</span>
            SIDE A
            <span style={{ color: '#d4a060' }}>✧</span>
          </div>
          <div style={{ flex: 1, height: 1, background: 'linear-gradient(90deg, rgba(200,180,150,0.2), transparent)' }}/>
        </div>
        
        {/* Track list */}
        <div style={{ marginBottom: 36 }}>
          {tracks.map((track, i) => (
            <div key={track.id}>
              {/* Track row */}
              <div 
                className="track-row"
                onClick={() => setExpandedTrack(expandedTrack === i ? -1 : i)}
                style={{
                  display: 'grid',
                  gridTemplateColumns: '44px 1fr 100px',
                  padding: '16px 18px',
                  borderBottom: expandedTrack === i ? 'none' : '1px solid rgba(200,180,150,0.06)',
                  cursor: 'pointer',
                  background: expandedTrack === i ? 'rgba(212,160,96,0.05)' : 'transparent',
                  borderLeft: expandedTrack === i ? '3px solid #d4a060' : '3px solid transparent',
                  borderRadius: expandedTrack === i ? '8px 0 0 0' : 0,
                  alignItems: 'center'
                }}
              >
                <div style={{ 
                  color: expandedTrack === i ? '#d4a060' : '#6a6458', 
                  fontSize: 15,
                  fontFamily: 'system-ui'
                }}>
                  {expandedTrack === i ? '▶' : `${i + 1}.`}
                </div>
                <div>
                  <div style={{ fontSize: 16, marginBottom: 4, color: expandedTrack === i ? '#e8e0d4' : '#c8c0b4' }}>{track.title}</div>
                  <div style={{ color: '#5a8a7a', fontSize: 11, fontFamily: 'system-ui' }}>● {track.versions} recordings</div>
                </div>
                <div style={{ 
                  display: 'flex', 
                  alignItems: 'center', 
                  justifyContent: 'flex-end',
                  gap: 12,
                  color: '#6a6458',
                  fontSize: 13,
                  fontFamily: 'system-ui'
                }}>
                  {expandedTrack === i && <span style={{ color: '#d4a060', fontSize: 16 }}>+</span>}
                  {track.duration}
                  <span style={{ color: expandedTrack === i ? '#d4a060' : '#4a4640', fontSize: 11 }}>
                    {expandedTrack === i ? '▲' : '▼'}
                  </span>
                </div>
              </div>
              
              {/* Expanded recordings */}
              {expandedTrack === i && (
                <div style={{
                  background: 'rgba(212,160,96,0.02)',
                  borderLeft: '3px solid #d4a060',
                  borderBottom: '1px solid rgba(200,180,150,0.06)',
                  borderRadius: '0 0 0 8px',
                  padding: '20px 24px'
                }}>
                  <div style={{
                    display: 'flex',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    marginBottom: 18
                  }}>
                    <div style={{ color: '#8a8478', fontSize: 13 }}>
                      Choose your recording ✦
                    </div>
                    <select style={{
                      background: '#2d2a26',
                      border: '1px solid #4a4640',
                      color: '#a8a098',
                      padding: '8px 14px',
                      borderRadius: 6,
                      fontSize: 12,
                      fontFamily: 'system-ui'
                    }}>
                      <option>Best Rated</option>
                      <option>Newest</option>
                      <option>Longest</option>
                    </select>
                  </div>
                  
                  {/* Recording cards */}
                  <div style={{ display: 'flex', gap: 16, overflowX: 'auto', paddingBottom: 8 }}>
                    {recordings.map((rec, j) => {
                      const isSelected = j === selectedRecording;
                      return (
                        <div 
                          key={j}
                          className="recording-card"
                          onClick={(e) => { e.stopPropagation(); setSelectedRecording(j); }}
                          style={{
                            minWidth: 240,
                            background: isSelected 
                              ? 'linear-gradient(180deg, #f5f0e8 0%, #e8e0d0 100%)' 
                              : 'linear-gradient(180deg, #2d2a26 0%, #242220 100%)',
                            border: isSelected ? '2px solid #d4a060' : '1px solid #3a3632',
                            borderRadius: 12,
                            overflow: 'hidden',
                            cursor: 'pointer'
                          }}
                        >
                          {/* Card header */}
                          <div style={{
                            padding: '16px 18px 12px',
                            borderBottom: isSelected ? '1px solid rgba(120,100,70,0.12)' : '1px solid rgba(200,180,150,0.06)',
                            display: 'flex',
                            justifyContent: 'space-between',
                            alignItems: 'flex-start'
                          }}>
                            <div style={{ 
                              fontSize: 36, 
                              fontWeight: 600,
                              color: isSelected ? '#2a2420' : '#a8a098',
                              lineHeight: 1
                            }}>{rec.year}</div>
                            
                            {isSelected && (
                              <div style={{
                                background: 'linear-gradient(135deg, #a85a38, #7a4020)',
                                color: '#f5f0e8',
                                fontSize: 8,
                                fontWeight: 700,
                                padding: '5px 10px',
                                borderRadius: 4,
                                letterSpacing: 1,
                                fontFamily: 'system-ui'
                              }}>⚡ PLAYING</div>
                            )}
                          </div>
                          
                          {/* Card body */}
                          <div style={{ padding: '14px 18px' }}>
                            <div style={{ 
                              fontSize: 14, 
                              color: isSelected ? '#3a3430' : '#c8c0b4',
                              marginBottom: 4,
                              fontWeight: 500
                            }}>{rec.venue}</div>
                            <div style={{ 
                              fontSize: 12, 
                              color: isSelected ? '#5a5045' : '#8a8478',
                              marginBottom: 14,
                              fontStyle: 'italic'
                            }}>{rec.location}</div>
                            
                            {[
                              ['Date', rec.date],
                              ['Rating', `★★★★★ (${rec.rating})`],
                              ['Length', rec.length],
                              ['Taper', rec.taper]
                            ].map(([label, value], k) => (
                              <div key={k} style={{
                                display: 'flex',
                                justifyContent: 'space-between',
                                marginBottom: 5,
                                fontSize: 11,
                                fontFamily: 'system-ui'
                              }}>
                                <span style={{ color: isSelected ? '#7a6a5a' : '#6a6458' }}>{label}</span>
                                <span style={{ 
                                  color: label === 'Rating' 
                                    ? (isSelected ? '#a85a38' : '#d4a060')
                                    : (isSelected ? '#3a3430' : '#a8a098'),
                                  maxWidth: 130,
                                  overflow: 'hidden',
                                  textOverflow: 'ellipsis',
                                  whiteSpace: 'nowrap'
                                }}>{value}</span>
                              </div>
                            ))}
                          </div>
                          
                          {/* Card buttons */}
                          <div style={{
                            display: 'flex',
                            gap: 10,
                            padding: '14px 18px',
                            borderTop: isSelected ? '1px solid rgba(120,100,70,0.08)' : '1px solid rgba(200,180,150,0.04)'
                          }}>
                            <button style={{
                              flex: 1,
                              padding: '11px 0',
                              background: isSelected 
                                ? 'linear-gradient(135deg, #d4a060, #b88030)' 
                                : 'linear-gradient(135deg, #3a3632, #2d2a26)',
                              border: 'none',
                              borderRadius: 6,
                              color: isSelected ? '#1c1a17' : '#a8a098',
                              fontSize: 12,
                              fontWeight: isSelected ? 600 : 400,
                              cursor: 'pointer',
                              fontFamily: 'system-ui'
                            }}>▶ Play</button>
                            <button style={{
                              flex: 1,
                              padding: '11px 0',
                              background: 'transparent',
                              border: isSelected ? '1px solid #c8a870' : '1px solid #4a4640',
                              borderRadius: 6,
                              color: isSelected ? '#6a5a4a' : '#8a8478',
                              fontSize: 12,
                              cursor: 'pointer',
                              fontFamily: 'system-ui'
                            }}>+ Queue</button>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              )}
            </div>
          ))}
        </div>
        
        {/* Side B divider */}
        <div style={{ 
          display: 'flex', 
          alignItems: 'center', 
          gap: 16, 
          marginBottom: 28 
        }}>
          <div style={{ flex: 1, height: 1, background: 'linear-gradient(90deg, transparent, rgba(200,180,150,0.2))' }}/>
          <div style={{ 
            color: '#6a6458', 
            fontSize: 11, 
            letterSpacing: 4,
            fontFamily: 'system-ui',
            display: 'flex',
            alignItems: 'center',
            gap: 10
          }}>
            <span style={{ color: '#7a6a80' }}>☽</span>
            SIDE B
            <span style={{ color: '#7a6a80' }}>☽</span>
          </div>
          <div style={{ flex: 1, height: 1, background: 'linear-gradient(90deg, rgba(200,180,150,0.2), transparent)' }}/>
        </div>
        
        {/* More tracks placeholder */}
        <div style={{ 
          textAlign: 'center', 
          color: '#5a5550', 
          fontSize: 14,
          padding: '32px 0',
          fontStyle: 'italic'
        }}>
          + 17 more tracks on Side B...
        </div>
        
        {/* Footer */}
        <div style={{ 
          marginTop: 56, 
          textAlign: 'center', 
          color: '#4a4640', 
          fontSize: 11,
          fontFamily: 'system-ui',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          gap: 8
        }}>
          <div style={{ color: '#6a6458' }}>☮ Please copy freely — never sell ☮</div>
          <div>POWERED BY ARCHIVE.ORG</div>
        </div>
      </div>
    </div>
  );
};

export default EarthyOrganicCampfire;
